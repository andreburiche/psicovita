<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\AsaasApiException;
use App\Enums\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentGatewayTransaction;
use App\Models\SessionParticipant;
use App\Models\TherapySession;
use App\Models\User;
use App\Notifications\PatientPaymentDueNotification;
use App\Notifications\PaymentAwaitingManualConfirmationNotification;
use App\Notifications\ProfessionalClinicalPaymentNotification;
use App\Support\ContactHasher;
use App\Support\PaymentMethodResolution;
use App\Support\PixCheckout;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly PatientService $patients,
        private readonly PaymentSettingsService $paymentSettings,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function resolvePatientForParticipant(SessionParticipant $participant, User $professional): Patient
    {
        $practiceId = $professional->clinicalPracticeId();

        if ($participant->patient_id) {
            return Patient::query()
                ->where('professional_id', $practiceId)
                ->findOrFail($participant->patient_id);
        }

        $email = strtolower(trim((string) $participant->email));
        $name = trim((string) $participant->display_name);

        if ($email === '' && $participant->user_id) {
            $linkedUser = User::query()->find($participant->user_id);
            $email = strtolower(trim((string) ($linkedUser?->normalizedEmail() ?? '')));
            if ($name === '') {
                $name = trim((string) ($linkedUser?->name ?? ''));
            }
        }

        if ($email !== '') {
            $existing = Patient::query()
                ->where('professional_id', $practiceId)
                ->where('email_hash', ContactHasher::emailHash($email))
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return $this->patients->create($professional, [
            'name' => $name !== '' ? $name : __('Participante'),
            'email' => $email !== '' ? $email : null,
            'notes' => __('Contacto vinculado à cobrança da sessão #:id', [
                'id' => $participant->therapy_session_id,
            ]),
        ]);
    }

    public function create(array $data, User $professional): Payment
    {
        $amount = round((float) $data['amount'], 2);
        $split = $this->calculateSplit($amount);
        $status = PaymentStatus::from((string) $data['status']);

        $payment = Payment::query()->create([
            'patient_id' => (int) $data['patient_id'],
            'therapy_session_id' => $data['therapy_session_id'] ?? null,
            'amount' => $amount,
            'status' => $status,
            'payment_method' => isset($data['payment_method']) && $data['payment_method'] !== ''
                ? PaymentMethod::from((string) $data['payment_method'])
                : null,
            'notes' => $data['notes'] ?? null,
            'gateway' => PaymentGateway::Manual,
            'platform_fee' => $split['platform_fee'],
            'professional_amount' => $split['professional_amount'],
            'paid_at' => $status === PaymentStatus::Paid ? now() : null,
        ]);

        if ($status === PaymentStatus::Pending) {
            $this->notifyPatientAboutPayment($payment, 'created');
        }

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Payment $payment, array $data): Payment
    {
        $amount = round((float) $data['amount'], 2);
        $split = $this->calculateSplit($amount);
        $newStatus = PaymentStatus::from((string) $data['status']);
        $previousStatus = $payment->status;

        $attributes = [
            'amount' => $amount,
            'status' => $newStatus,
            'payment_method' => isset($data['payment_method']) && $data['payment_method'] !== ''
                ? PaymentMethod::from((string) $data['payment_method'])
                : null,
            'notes' => $data['notes'] ?? null,
            'platform_fee' => $split['platform_fee'],
            'professional_amount' => $split['professional_amount'],
        ];

        if ($newStatus === PaymentStatus::Paid && $previousStatus !== PaymentStatus::Paid) {
            $attributes['paid_at'] = now();
            $attributes['refunded_at'] = null;
        }

        if ($newStatus === PaymentStatus::Refunded) {
            $attributes['refunded_at'] = now();
        }

        if ($newStatus !== PaymentStatus::Paid && $newStatus !== PaymentStatus::Refunded) {
            $attributes['paid_at'] = null;
            $attributes['refunded_at'] = null;
        }

        $payment->update($attributes);

        return $payment->fresh();
    }

    public function createFromSession(TherapySession $session, ?float $amount = null): Payment
    {
        $existing = Payment::query()
            ->where('therapy_session_id', $session->id)
            ->when($session->patient_id, fn ($query) => $query->where('patient_id', $session->patient_id))
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $amount ??= (float) config('payment.default_session_amount', 150);
        $split = $this->calculateSplit($amount);

        $payment = Payment::query()->create([
            'patient_id' => $session->patient_id,
            'therapy_session_id' => $session->id,
            'amount' => round($amount, 2),
            'status' => PaymentStatus::Pending,
            'payment_method' => null,
            'notes' => null,
            'gateway' => PaymentGateway::Manual,
            'platform_fee' => $split['platform_fee'],
            'professional_amount' => $split['professional_amount'],
        ]);

        $this->notifyPatientAboutPayment($payment, 'created');

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function markAsPaid(Payment $payment, array $meta = []): Payment
    {
        $wasPayable = in_array($payment->status, [
            PaymentStatus::Pending,
            PaymentStatus::Overdue,
            PaymentStatus::PendingConfirmation,
        ], true);

        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'refunded_at' => null,
            'gateway' => isset($meta['gateway'])
                ? PaymentGateway::from((string) $meta['gateway'])
                : $payment->gateway,
            'external_id' => $meta['external_id'] ?? $payment->external_id,
            'payment_method' => isset($meta['payment_method']) && $meta['payment_method'] !== ''
                ? PaymentMethod::from((string) $meta['payment_method'])
                : $payment->payment_method,
        ]);

        $payment = $payment->fresh();

        if ($wasPayable) {
            $this->notifyProfessionalAboutPayment($payment, 'received');
        }

        return $payment;
    }

    public function markAsOverdue(Payment $payment, array $meta = []): Payment
    {
        $payment->update([
            'status' => PaymentStatus::Overdue,
            'gateway' => isset($meta['gateway'])
                ? PaymentGateway::from((string) $meta['gateway'])
                : $payment->gateway,
            'external_id' => $meta['external_id'] ?? $payment->external_id,
        ]);

        $payment = $payment->fresh();

        $this->notifyPatientAboutPayment($payment, 'overdue');
        $this->notifyProfessionalAboutPayment($payment, 'overdue');

        return $payment;
    }

    /**
     * @return array{platform_fee: float, professional_amount: float}
     */
    public function calculateSplit(float $amount): array
    {
        $amount = round(max(0, $amount), 2);
        $percent = max(0, min(100, (float) config('payment.platform_fee_percent', 10)));
        $platformFee = round($amount * ($percent / 100), 2);
        $professionalAmount = round($amount - $platformFee, 2);

        return [
            'platform_fee' => $platformFee,
            'professional_amount' => $professionalAmount,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirmFromWebhook(array $payload): ?Payment
    {
        $parsed = $this->gateway->handleWebhook($payload);

        PaymentGatewayTransaction::query()->create([
            'payment_id' => null,
            'gateway' => PaymentGateway::Asaas,
            'event_type' => $parsed['event'],
            'external_id' => $parsed['external_id'],
            'status' => $parsed['status'] ?? 'received',
            'payload' => $parsed['raw'],
        ]);

        if ($parsed['external_id'] === null) {
            return null;
        }

        $payment = Payment::query()
            ->where('external_id', $parsed['external_id'])
            ->first();

        if ($payment === null) {
            return null;
        }

        PaymentGatewayTransaction::query()
            ->where('external_id', $parsed['external_id'])
            ->whereNull('payment_id')
            ->latest('id')
            ->limit(1)
            ->update(['payment_id' => $payment->id]);

        if ($this->isOverdueWebhook($parsed)) {
            if (! in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Refunded, PaymentStatus::Cancelled], true)) {
                return $this->markAsOverdue($payment, [
                    'gateway' => PaymentGateway::Asaas->value,
                    'external_id' => $parsed['external_id'],
                ]);
            }

            return $payment;
        }

        if (in_array($parsed['status'], ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true)) {
            return $this->markAsPaid($payment, [
                'gateway' => PaymentGateway::Asaas->value,
                'external_id' => $parsed['external_id'],
            ]);
        }

        return $payment;
    }

    /**
     * @param  array{event: string, external_id: string|null, status: string|null, raw: array<string, mixed>}  $parsed
     */
    private function isOverdueWebhook(array $parsed): bool
    {
        if ($parsed['event'] === 'PAYMENT_OVERDUE') {
            return true;
        }

        return in_array($parsed['status'], ['OVERDUE'], true);
    }

    public function resolvePatientFichaForUser(User $user): ?Patient
    {
        if (! $user->usesPatientPortalExperience()) {
            return null;
        }

        $email = $user->normalizedEmail();
        if ($email === '') {
            return null;
        }

        $query = Patient::query()->where('email_hash', ContactHasher::emailHash($email));

        if ($user->isPatient() && $user->professional_id !== null) {
            $query->where('professional_id', $user->professional_id);
        }

        return $query->orderBy('id')->first();
    }

    public function patientOwnsPayment(User $user, Payment $payment): bool
    {
        $ficha = $this->resolvePatientFichaForUser($user);

        return $ficha !== null && (int) $payment->patient_id === (int) $ficha->id;
    }

    public function paginatePortalPayments(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $ficha = $this->resolvePatientFichaForUser($user);

        if ($ficha === null) {
            return Payment::query()->whereRaw('0 = 1')->paginate($perPage);
        }

        return Payment::query()
            ->where('patient_id', $ficha->id)
            ->with(['therapySession'])
            ->latest()
            ->paginate($perPage);
    }

    public function needsPaymentMethodChoice(Payment $payment): bool
    {
        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Overdue], true)) {
            return false;
        }

        if ($payment->payment_method !== null) {
            return false;
        }

        $resolution = $this->resolveCheckoutForPayment($payment);

        // Modo manual não oferece cartão Asaas — vai directo ao PIX do profissional.
        return $resolution->isAsaas();
    }

    public function resolveCheckoutForPayment(Payment $payment): PaymentMethodResolution
    {
        $payment->loadMissing('patient.professional');

        return $this->paymentSettings->resolveForPatientProfessional($payment->patient);
    }

    public function initiatePortalPayment(Payment $payment, ?PaymentMethod $chosenMethod = null): Payment
    {
        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Overdue], true)) {
            throw new \InvalidArgumentException(__('Este pagamento não está pendente.'));
        }

        $payment->loadMissing('patient.professional');
        $patient = $payment->patient;
        if ($patient === null) {
            throw new \InvalidArgumentException(__('Paciente não encontrado para esta cobrança.'));
        }

        $resolution = $this->paymentSettings->resolveForPatientProfessional($patient);

        if ($resolution->isNotConfigured()) {
            throw new \InvalidArgumentException(__('Este profissional ainda não configurou o recebimento. Entre em contacto diretamente.'));
        }

        if ($resolution->isManual()) {
            return $this->prepareManualPixCheckout($payment, $resolution);
        }

        $method = $payment->payment_method ?? $chosenMethod;
        if ($method === null) {
            throw new \InvalidArgumentException(__('Selecione PIX ou cartão para continuar.'));
        }

        try {
            $customerId = $this->resolveAsaasCustomer($patient);
            $billingType = $this->billingTypeForMethod($method);

            $chargePayload = [
                'customer_id' => $customerId,
                'amount' => (float) $payment->amount,
                'billing_type' => $billingType,
                'due_date' => now()->addDays(3)->toDateString(),
                'description' => $payment->therapy_session_id
                    ? __('Sessão #:id', ['id' => $payment->therapy_session_id])
                    : __('Consulta PsiConecta'),
            ];

            $split = $this->buildChargeSplit($payment);
            if ($split !== null) {
                $chargePayload['split'] = $split;
            }

            $charge = $this->gateway->createCharge($chargePayload);

            $gatewayMeta = [
                'billing_type' => $billingType,
                'invoice_url' => $charge['raw']['invoiceUrl'] ?? null,
                'stub' => $charge['raw']['stub'] ?? false,
                'checkout_mode' => PaymentMethodResolution::MODE_ASAAS,
                'pix' => $billingType === 'PIX' ? ($charge['pix'] ?? null) : null,
            ];

            $payment->update([
                'gateway' => PaymentGateway::Asaas,
                'external_id' => $charge['external_id'],
                'payment_method' => $method,
                'gateway_meta' => $gatewayMeta,
            ]);

            PaymentGatewayTransaction::query()->create([
                'payment_id' => $payment->id,
                'gateway' => PaymentGateway::Asaas,
                'event_type' => 'CHARGE_CREATED',
                'external_id' => $charge['external_id'],
                'status' => $charge['status'],
                'payload' => $charge['raw'],
            ]);

            return $payment->fresh();
        } catch (AsaasApiException $e) {
            throw new \InvalidArgumentException($e->getMessage(), previous: $e);
        }
    }

    public function prepareManualPixCheckout(Payment $payment, ?PaymentMethodResolution $resolution = null): Payment
    {
        $resolution ??= $this->resolveCheckoutForPayment($payment);

        if (! $resolution->isManual() || ! $resolution->hasManualPix()) {
            throw new \InvalidArgumentException(__('PIX manual não está configurado para este profissional.'));
        }

        $pix = [
            'payload' => $resolution->pixManualLink,
            'image_url' => $resolution->pixQrcodeUrl,
            'encoded_image' => null,
            'image_mime' => 'image/jpeg',
            'raw' => ['manual' => true],
        ];

        $payment->update([
            'gateway' => PaymentGateway::Manual,
            'payment_method' => PaymentMethod::Pix,
            'gateway_meta' => array_merge($payment->gateway_meta ?? [], [
                'checkout_mode' => PaymentMethodResolution::MODE_MANUAL,
                'pix' => $pix,
                'pix_manual_link' => $resolution->pixManualLink,
                'pix_qrcode_url' => $resolution->pixQrcodeUrl,
            ]),
        ]);

        return $payment->fresh();
    }

    public function markAwaitingManualConfirmation(Payment $payment): Payment
    {
        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Overdue], true)) {
            throw new \InvalidArgumentException(__('Este pagamento não pode ser marcado como aguardando confirmação.'));
        }

        $resolution = $this->resolveCheckoutForPayment($payment);
        if (! $resolution->isManual()) {
            throw new \InvalidArgumentException(__('Só é possível confirmar manualmente pagamentos PIX manuais.'));
        }

        $payment->update([
            'status' => PaymentStatus::PendingConfirmation,
            'payment_method' => PaymentMethod::Pix,
            'gateway' => PaymentGateway::Manual,
            'gateway_meta' => array_merge($payment->gateway_meta ?? [], [
                'checkout_mode' => PaymentMethodResolution::MODE_MANUAL,
                'manual_paid_reported_at' => now()->toIso8601String(),
            ]),
        ]);

        $payment = $payment->fresh(['patient.professional']);
        $owner = $this->paymentSettings->practiceOwnerForPayment($payment);
        if ($owner) {
            $owner->notify(new PaymentAwaitingManualConfirmationNotification($payment));
        }

        return $payment;
    }

    public function confirmManualPayment(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::PendingConfirmation) {
            throw new \InvalidArgumentException(__('Este pagamento não está aguardando confirmação manual.'));
        }

        return $this->markAsPaid($payment, [
            'gateway' => PaymentGateway::Manual->value,
            'payment_method' => PaymentMethod::Pix->value,
        ]);
    }

    public function syncPixCheckoutForDisplay(Payment $payment): Payment
    {
        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Overdue], true)) {
            return $payment;
        }

        if ($payment->payment_method !== PaymentMethod::Pix) {
            return $payment;
        }

        if ($payment->external_id === null || $payment->external_id === '') {
            return $payment;
        }

        if (PixCheckout::isAsaasStubMode()) {
            try {
                $pixData = $this->gateway->getPixQrCode($payment->external_id);

                if (PixCheckout::isDisplayable($pixData)) {
                    $payment->update([
                        'gateway_meta' => array_merge($payment->gateway_meta ?? [], [
                            'pix' => $pixData,
                        ]),
                    ]);

                    return $payment->fresh();
                }
            } catch (AsaasApiException) {
                // Continua com a lógica abaixo.
            }
        }

        $pix = $payment->gateway_meta['pix'] ?? null;
        if (is_array($pix) && PixCheckout::isDisplayable($pix)) {
            return $payment;
        }

        try {
            $pixData = $this->gateway->getPixQrCode($payment->external_id);
            $payment->update([
                'gateway_meta' => array_merge($payment->gateway_meta ?? [], [
                    'pix' => $pixData,
                ]),
            ]);
        } catch (AsaasApiException) {
            // Mantém a página utilizável mesmo se o QR não puder ser renovado.
        }

        return $payment->fresh();
    }

    public function portalPaymentSuccessMessage(Payment $payment): string
    {
        $mode = $payment->gateway_meta['checkout_mode'] ?? null;

        if ($mode === PaymentMethodResolution::MODE_MANUAL) {
            return __('Dados PIX do profissional. Escaneie o QR Code ou use a chave/link e depois toque em «Já paguei».');
        }

        return $payment->payment_method === PaymentMethod::Card
            ? __('Link de pagamento gerado. Conclua o pagamento com cartão no ambiente seguro.')
            : __('Cobrança PIX gerada. Escaneie o QR Code ou copie o código.');
    }

    private function billingTypeForMethod(PaymentMethod $method): string
    {
        return $method === PaymentMethod::Card ? 'CREDIT_CARD' : 'PIX';
    }

    /**
     * @return array{wallet_id: string, fixed_value: float}|null
     */
    private function buildChargeSplit(Payment $payment): ?array
    {
        if (! config('asaas.split_enabled')) {
            return null;
        }

        $payment->loadMissing('patient.professional');
        $owner = $this->paymentSettings->practiceOwnerForPayment($payment);
        $walletId = $owner?->asaas_wallet_id;
        if (! filled($walletId)) {
            return null;
        }

        $professionalAmount = (float) ($payment->professional_amount ?? 0);
        if ($professionalAmount <= 0) {
            $professionalAmount = $this->calculateSplit((float) $payment->amount)['professional_amount'];
        }

        if ($professionalAmount <= 0) {
            return null;
        }

        return [
            'wallet_id' => (string) $walletId,
            'fixed_value' => $professionalAmount,
        ];
    }

    private function resolveAsaasCustomer(Patient $patient): string
    {
        if (filled($patient->asaas_customer_id)) {
            return (string) $patient->asaas_customer_id;
        }

        $customerId = $this->gateway->ensureCustomer([
            'name' => $patient->name,
            'email' => $patient->email,
            'cpf' => $patient->cpf ? only_digits((string) $patient->cpf) : null,
            'phone' => $patient->phone ? only_digits((string) $patient->phone) : null,
            'external_reference' => 'patient:'.$patient->id,
        ]);

        $patient->update(['asaas_customer_id' => $customerId]);

        return $customerId;
    }

    /**
     * @return array{count: int, total: float, total_formatted: string}
     */
    public function pendingPortalSummary(int $professionalId): array
    {
        $query = Payment::query()
            ->whereHas('patient', fn ($q) => $q->where('professional_id', $professionalId))
            ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Overdue]);

        $count = (int) $query->count();
        $total = (float) (clone $query)->sum('amount');

        return [
            'count' => $count,
            'total' => $total,
            'total_formatted' => number_format($total, 2, ',', '.'),
        ];
    }

    /**
     * @return array{count: int, total: float, total_formatted: string}
     */
    public function patientPendingSummary(User $user): array
    {
        $ficha = $this->resolvePatientFichaForUser($user);

        if ($ficha === null) {
            return [
                'count' => 0,
                'total' => 0.0,
                'total_formatted' => '0,00',
            ];
        }

        $query = Payment::query()
            ->where('patient_id', $ficha->id)
            ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Overdue]);

        $count = (int) $query->count();
        $total = (float) (clone $query)->sum('amount');

        return [
            'count' => $count,
            'total' => $total,
            'total_formatted' => number_format($total, 2, ',', '.'),
        ];
    }

    public function paidRevenueQuery(int $professionalId): Builder
    {
        return Payment::query()
            ->whereHas('patient', fn ($q) => $q->where('professional_id', $professionalId))
            ->where('status', PaymentStatus::Paid);
    }

    public function sumPaidRevenueBetween(int $professionalId, Carbon $start, Carbon $end): float
    {
        return $this->sumPaidRevenueBreakdownBetween($professionalId, $start, $end)['gross'];
    }

    /**
     * @return array{gross: float, platform_fee: float, professional_amount: float}
     */
    public function sumPaidRevenueBreakdownBetween(int $professionalId, Carbon $start, Carbon $end): array
    {
        $query = $this->paidRevenueBetweenScope(
            $this->paidRevenueQuery($professionalId),
            $start,
            $end,
        );

        return [
            'gross' => round((float) (clone $query)->sum('amount'), 2),
            'platform_fee' => round((float) (clone $query)->sum('platform_fee'), 2),
            'professional_amount' => round((float) (clone $query)->sum('professional_amount'), 2),
        ];
    }

    public function sumPaidRevenueOnDate(int $professionalId, Carbon $day): float
    {
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        return $this->sumPaidRevenueBetween($professionalId, $start, $end);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    private function paidRevenueBetweenScope(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where(function (Builder $inner) use ($start, $end): void {
            $inner->whereBetween('paid_at', [$start, $end])
                ->orWhere(function (Builder $fallback) use ($start, $end): void {
                    $fallback->whereNull('paid_at')
                        ->whereBetween('created_at', [$start, $end]);
                });
        });
    }

    public function resolvePortalUserForPatient(Patient $patient): ?User
    {
        if (! filled($patient->email_hash)) {
            return null;
        }

        return User::query()
            ->where('email_hash', $patient->email_hash)
            ->where('role', UserRole::Patient)
            ->where('professional_id', $patient->professional_id)
            ->orderBy('id')
            ->first();
    }

    /**
     * @return Collection<int, Payment>
     */
    public function paymentsDueForPatientReminder(): Collection
    {
        $days = max(1, (int) config('payment.patient_reminder_days', 3));
        $cutoff = now()->subDays($days);

        return Payment::query()
            ->with('patient')
            ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Overdue])
            ->where('created_at', '<=', $cutoff)
            ->get();
    }

    public function notifyPatientAboutPayment(Payment $payment, string $context = 'created'): bool
    {
        if (! config('payment.patient_notifications_enabled', true)) {
            return false;
        }

        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Overdue], true)) {
            return false;
        }

        $payment->loadMissing('patient');
        $patient = $payment->patient;
        if ($patient === null) {
            return false;
        }

        $user = $this->resolvePortalUserForPatient($patient);
        if ($user === null) {
            return false;
        }

        if ($this->patientAlreadyNotified($user, $payment, $context)) {
            return false;
        }

        $user->notify(new PatientPaymentDueNotification($payment->fresh(['therapySession']), $context));

        return true;
    }

    private function patientAlreadyNotified(User $user, Payment $payment, string $context): bool
    {
        $query = $user->notifications()
            ->where('type', PatientPaymentDueNotification::class)
            ->where('data->payment_id', $payment->id)
            ->where('data->context', $context);

        if ($context === 'reminder') {
            $query->where('created_at', '>=', now()->subDays(7));
        }

        return $query->exists();
    }

    public function notifyProfessionalAboutPayment(Payment $payment, string $context = 'received'): bool
    {
        if (! config('payment.professional_notifications_enabled', true)) {
            return false;
        }

        $payment->loadMissing('patient');
        $patient = $payment->patient;
        if ($patient === null) {
            return false;
        }

        $professional = User::query()->find((int) $patient->professional_id);
        if ($professional === null || ! $professional->isProfessional()) {
            return false;
        }

        if ($this->professionalAlreadyNotified($professional, $payment, $context)) {
            return false;
        }

        $professional->notify(new ProfessionalClinicalPaymentNotification($payment->fresh(['patient']), $context));

        return true;
    }

    private function professionalAlreadyNotified(User $user, Payment $payment, string $context): bool
    {
        return $user->notifications()
            ->where('type', ProfessionalClinicalPaymentNotification::class)
            ->where('data->payment_id', $payment->id)
            ->where('data->context', $context)
            ->exists();
    }
}
