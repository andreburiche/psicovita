<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionPlanSlug;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Exceptions\AsaasApiException;
use App\Models\Patient;
use App\Models\ProfessionalSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionPaymentConfirmedAdminNotification;
use App\Support\PixCheckout;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SubscriptionService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {}
    public function activeSubscription(User $user): ?ProfessionalSubscription
    {
        $user = $this->billingUser($user);

        if (! $user->isProfessional()) {
            return null;
        }

        return $user->professionalSubscription()
            ->with('plan')
            ->first();
    }

    public function billingUser(User $user): User
    {
        if ($user->isProfessional() && $user->clinic_owner_id !== null) {
            $owner = User::query()->find((int) $user->clinic_owner_id);

            return $owner ?? $user;
        }

        return $user;
    }

    public function isExempt(User $user): bool
    {
        return $user->isAdmin();
    }

    public function isActive(User $user): bool
    {
        if ($this->isExempt($user)) {
            return true;
        }

        if (! $user->isProfessional()) {
            return true;
        }

        $user = $this->billingUser($user);

        $subscription = $this->activeSubscription($user);

        if ($subscription === null) {
            return false;
        }

        return $this->subscriptionIsCurrentlyValid($subscription);
    }

    public function canUseFeature(User $user, string $feature): bool
    {
        if ($this->isExempt($user)) {
            return true;
        }

        if (! $user->isProfessional()) {
            return true;
        }

        $user = $this->billingUser($user);

        if (! $this->isActive($user)) {
            return false;
        }

        $subscription = $this->activeSubscription($user);
        if ($subscription === null || $subscription->plan === null) {
            return false;
        }

        return $subscription->plan->hasFeature($feature);
    }

    public function patientLimit(User $user): ?int
    {
        if ($this->isExempt($user) || ! $user->isProfessional()) {
            return null;
        }

        $billingUser = $this->billingUser($user);
        $subscription = $this->activeSubscription($billingUser);
        $max = $subscription?->plan?->max_patients;

        if ($max === null || $max <= 0) {
            return null;
        }

        return $max;
    }

    public function activePatientCount(User $user): int
    {
        if (! $user->isProfessional()) {
            return 0;
        }

        return Patient::query()
            ->where('professional_id', $this->billingUser($user)->clinicalPracticeId())
            ->count();
    }

    public function canAddPatient(User $user): bool
    {
        $limit = $this->patientLimit($user);

        if ($limit === null) {
            return true;
        }

        return $this->activePatientCount($user) < $limit;
    }

    /**
     * @return array{
     *   limited: bool,
     *   limit: int|null,
     *   count: int,
     *   remaining: int|null,
     *   at_limit: bool,
     *   near_limit: bool
     * }
     */
    public function patientQuotaContext(User $user): array
    {
        $limit = $this->patientLimit($user);
        $count = $this->activePatientCount($user);

        if ($limit === null) {
            return [
                'limited' => false,
                'limit' => null,
                'count' => $count,
                'remaining' => null,
                'at_limit' => false,
                'near_limit' => false,
            ];
        }

        $remaining = max(0, $limit - $count);
        $nearThreshold = max(1, (int) min(5, ceil($limit * 0.1)));

        return [
            'limited' => true,
            'limit' => $limit,
            'count' => $count,
            'remaining' => $remaining,
            'at_limit' => $remaining === 0,
            'near_limit' => $remaining > 0 && $remaining <= $nearThreshold,
        ];
    }

    /**
     * @return Collection<int, SubscriptionPlan>
     */
    public function purchasablePlans(): Collection
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->where('slug', '!=', SubscriptionPlanSlug::Trial)
            ->where('price_cents', '>', 0)
            ->orderBy('sort_order')
            ->get();
    }

    public function initiateCheckout(User $user, SubscriptionPlan $plan, PaymentMethod $method, BillingCycle $billingCycle = BillingCycle::Monthly): ProfessionalSubscription
    {
        if (! $user->isProfessional()) {
            throw new \InvalidArgumentException(__('Apenas profissionais podem subscrever um plano.'));
        }

        if ($user->isClinicTeamMember()) {
            throw new \InvalidArgumentException(__('A assinatura é gerida pelo titular da clínica.'));
        }

        if ($plan->slug === SubscriptionPlanSlug::Trial || $plan->price_cents <= 0) {
            throw new \InvalidArgumentException(__('Este plano não está disponível para compra online.'));
        }

        if ($billingCycle === BillingCycle::Yearly && $plan->resolvedAnnualPriceCents() <= 0) {
            throw new \InvalidArgumentException(__('Plano anual indisponível para este plano.'));
        }

        $billingType = $method === PaymentMethod::Card ? 'CREDIT_CARD' : 'PIX';
        $amount = round($plan->chargeAmountCents($billingCycle) / 100, 2);
        $isStub = ! config('asaas.enabled') || ! filled(config('asaas.api_key'));

        try {
            $existing = ProfessionalSubscription::query()->where('user_id', $user->id)->first();
            if ($existing !== null && filled($existing->gateway_external_id)) {
                $this->cancelGatewaySubscriptionRecord($existing);
            }

            $customerId = $this->resolveAsaasCustomer($user);
            $result = $this->gateway->createSubscription([
                'customer_id' => $customerId,
                'amount' => $amount,
                'billing_type' => $billingType,
                'cycle' => $billingCycle->asaasCycle(),
                'description' => __('PsiConecta — :plan (:cycle)', [
                    'plan' => $plan->name,
                    'cycle' => $billingCycle->label(),
                ]),
                'next_due_date' => now()->addDay()->toDateString(),
            ]);

            $firstPayment = is_array($result['first_payment'] ?? null) ? $result['first_payment'] : null;
            $gatewayMeta = [
                'billing_type' => $billingType,
                'billing_cycle' => $billingCycle->value,
                'payment_method' => $method->value,
                'first_payment_external_id' => $firstPayment['external_id'] ?? null,
                'invoice_url' => $firstPayment['invoice_url'] ?? null,
                'pix' => $firstPayment['pix'] ?? null,
                'stub' => (bool) (($result['raw']['stub'] ?? false) || ($firstPayment['raw']['stub'] ?? false)),
            ];

            $stubEndsAt = $billingCycle === BillingCycle::Yearly
                ? now()->addYear()
                : now()->addMonth();

            $subscription = ProfessionalSubscription::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'status' => $isStub ? SubscriptionStatus::Active : SubscriptionStatus::PastDue,
                    'starts_at' => now(),
                    'ends_at' => $isStub ? $stubEndsAt : null,
                    'trial_ends_at' => null,
                    'gateway_external_id' => $result['external_id'],
                    'gateway_meta' => $gatewayMeta,
                    'cancelled_at' => null,
                ],
            );

            return $subscription->fresh(['plan']);
        } catch (AsaasApiException $e) {
            throw new \InvalidArgumentException($e->getMessage(), previous: $e);
        }
    }

    public function syncCheckoutForDisplay(ProfessionalSubscription $subscription): ProfessionalSubscription
    {
        $method = isset($subscription->gateway_meta['payment_method'])
            ? PaymentMethod::from((string) $subscription->gateway_meta['payment_method'])
            : null;

        if ($method !== PaymentMethod::Pix) {
            return $subscription;
        }

        if (PixCheckout::isAsaasStubMode()) {
            try {
                $chargeId = (string) ($subscription->gateway_meta['first_payment_external_id'] ?? 'static-fallback');
                $pixData = $this->gateway->getPixQrCode($chargeId);

                if (PixCheckout::isDisplayable($pixData)) {
                    $subscription->update([
                        'gateway_meta' => array_merge($subscription->gateway_meta ?? [], [
                            'pix' => $pixData,
                        ]),
                    ]);

                    return $subscription->fresh(['plan']);
                }
            } catch (AsaasApiException) {
                // Continua com a lógica abaixo.
            }
        }

        $pix = $subscription->gateway_meta['pix'] ?? null;
        if (is_array($pix) && PixCheckout::isDisplayable($pix)) {
            return $subscription;
        }

        $paymentExternalId = $subscription->gateway_meta['first_payment_external_id'] ?? null;

        if (($paymentExternalId === null || $paymentExternalId === '') && filled($subscription->gateway_external_id)) {
            try {
                $firstPayment = $this->gateway->getFirstPendingSubscriptionPayment(
                    (string) $subscription->gateway_external_id,
                    (string) ($subscription->gateway_meta['billing_type'] ?? 'PIX'),
                );

                if (is_array($firstPayment)) {
                    $paymentExternalId = $firstPayment['external_id'] ?? null;
                    $subscription->update([
                        'gateway_meta' => array_merge($subscription->gateway_meta ?? [], array_filter([
                            'first_payment_external_id' => $paymentExternalId,
                            'invoice_url' => $firstPayment['invoice_url'] ?? null,
                            'pix' => $firstPayment['pix'] ?? null,
                        ], fn ($value) => $value !== null)),
                    ]);
                    $subscription = $subscription->fresh(['plan']) ?? $subscription;

                    $pix = $subscription->gateway_meta['pix'] ?? null;
                    if (is_array($pix) && PixCheckout::isDisplayable($pix)) {
                        return $subscription;
                    }
                }
            } catch (AsaasApiException) {
                // Mantém a página utilizável.
            }
        }

        if ($paymentExternalId === null || $paymentExternalId === '') {
            return $subscription;
        }

        try {
            $pixData = $this->gateway->getPixQrCode((string) $paymentExternalId);
            $subscription->update([
                'gateway_meta' => array_merge($subscription->gateway_meta ?? [], [
                    'pix' => $pixData,
                ]),
            ]);
        } catch (AsaasApiException) {
            // Mantém a página utilizável.
        }

        return $subscription->fresh(['plan']);
    }

    public function shouldShowPixCheckout(ProfessionalSubscription $subscription): bool
    {
        $meta = $subscription->gateway_meta ?? [];
        $method = isset($meta['payment_method'])
            ? PaymentMethod::from((string) $meta['payment_method'])
            : null;

        if ($method !== PaymentMethod::Pix) {
            return false;
        }

        $pix = $meta['pix'] ?? null;
        if (! is_array($pix) || ! PixCheckout::isDisplayable($pix)) {
            return false;
        }

        if ($subscription->status === SubscriptionStatus::PastDue) {
            return true;
        }

        return (bool) ($meta['stub'] ?? false);
    }

    public function checkoutSuccessMessage(ProfessionalSubscription $subscription): string
    {
        $method = isset($subscription->gateway_meta['payment_method'])
            ? PaymentMethod::from((string) $subscription->gateway_meta['payment_method'])
            : PaymentMethod::Pix;

        if ($method === PaymentMethod::Card) {
            return __('Assinatura criada. Conclua o primeiro pagamento com cartão no ambiente seguro.');
        }

        return __('Assinatura criada. Escaneie o QR Code PIX para activar o plano.');
    }

    public function isCancellable(ProfessionalSubscription $subscription): bool
    {
        if ($subscription->cancelled_at !== null) {
            return false;
        }

        return filled($subscription->gateway_external_id)
            && in_array($subscription->status, [
                SubscriptionStatus::Active,
                SubscriptionStatus::PastDue,
            ], true);
    }

    public function cancelGatewaySubscription(User $user): ProfessionalSubscription
    {
        if (! $user->isProfessional()) {
            throw new \InvalidArgumentException(__('Apenas profissionais podem cancelar a assinatura.'));
        }

        $subscription = $this->activeSubscription($user);
        if ($subscription === null) {
            throw new \InvalidArgumentException(__('Não há assinatura para cancelar.'));
        }

        if (! $this->isCancellable($subscription)) {
            throw new \InvalidArgumentException(__('Esta assinatura não pode ser cancelada online.'));
        }

        $this->cancelGatewaySubscriptionRecord($subscription);

        $subscription->update([
            'cancelled_at' => now(),
            'gateway_meta' => array_merge($subscription->gateway_meta ?? [], [
                'cancelled_at' => now()->toIso8601String(),
            ]),
        ]);

        return $subscription->fresh(['plan']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirmFromWebhook(array $payload): ?ProfessionalSubscription
    {
        $parsed = $this->gateway->handleWebhook($payload);
        $subscriptionExternalId = $parsed['subscription_external_id'] ?? null;

        if ($subscriptionExternalId === null || $subscriptionExternalId === '') {
            return null;
        }

        $subscription = ProfessionalSubscription::query()
            ->where('gateway_external_id', $subscriptionExternalId)
            ->first();

        if ($subscription === null) {
            return null;
        }

        if ($this->isOverdueWebhook($parsed)) {
            if ($subscription->status !== SubscriptionStatus::PastDue) {
                $subscription->update(['status' => SubscriptionStatus::PastDue]);
            }

            return $subscription->fresh(['plan']);
        }

        if (! in_array($parsed['status'], ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true)) {
            return null;
        }

        $paymentExternalId = $parsed['external_id'] ?? null;
        if ($paymentExternalId !== null && $paymentExternalId === ($subscription->gateway_meta['last_renewal_payment_id'] ?? null)) {
            return $subscription;
        }

        $isRenewal = filled($subscription->gateway_meta['last_renewal_at'] ?? null)
            || $subscription->status === SubscriptionStatus::Active;

        $endsAt = $this->nextRenewalEndsAt($subscription);

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'ends_at' => $endsAt,
            'gateway_meta' => array_merge($subscription->gateway_meta ?? [], array_filter([
                'last_renewal_payment_id' => $paymentExternalId,
                'last_renewal_at' => now()->toIso8601String(),
            ])),
        ]);

        $subscription = $subscription->fresh(['plan', 'user']);
        $this->notifyAdminsAboutSubscriptionPayment($subscription, $isRenewal);

        return $subscription;
    }

    public function manualConfirmByAdmin(
        User $admin,
        ProfessionalSubscription $subscription,
        SubscriptionPlan $plan,
        BillingCycle $billingCycle,
        ?Carbon $validUntil = null,
        ?string $note = null,
    ): ProfessionalSubscription {
        if (! config('subscription.manual_activation_enabled', true)) {
            throw new \InvalidArgumentException(__('A validação manual de assinaturas está desactivada.'));
        }

        if ($plan->slug === SubscriptionPlanSlug::Trial || $plan->price_cents <= 0) {
            throw new \InvalidArgumentException(__('Seleccione um plano pago para validar o pagamento.'));
        }

        $endsAt = $validUntil ?? $this->manualActivationEndsAt($subscription, $billingCycle);

        $subscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => $subscription->starts_at ?? now(),
            'ends_at' => $endsAt,
            'trial_ends_at' => null,
            'cancelled_at' => null,
            'gateway_meta' => array_merge($subscription->gateway_meta ?? [], array_filter([
                'billing_cycle' => $billingCycle->value,
                'payment_method' => PaymentMethod::Manual->value,
                'manual_validated_at' => now()->toIso8601String(),
                'manual_validated_by' => $admin->id,
                'manual_validated_by_name' => $admin->name,
                'manual_note' => $note,
                'last_renewal_at' => now()->toIso8601String(),
            ], fn ($value) => $value !== null && $value !== '')),
        ]);

        $subscription = $subscription->fresh(['plan', 'user']);

        \App\Support\AuditTrail::entity('manual_confirm', 'professional_subscriptions', $subscription, [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing_cycle' => $billingCycle->value,
            'ends_at' => $endsAt->toIso8601String(),
            'note' => $note,
        ], $admin);

        return $subscription;
    }

    private function manualActivationEndsAt(ProfessionalSubscription $subscription, BillingCycle $billingCycle): Carbon
    {
        if ($subscription->status === SubscriptionStatus::Active && $subscription->ends_at?->isFuture()) {
            $base = $subscription->ends_at->copy();

            return $billingCycle === BillingCycle::Yearly
                ? $base->addYear()
                : $base->addMonth();
        }

        return $billingCycle === BillingCycle::Yearly
            ? now()->addYear()
            : now()->addMonth();
    }

    private function notifyAdminsAboutSubscriptionPayment(ProfessionalSubscription $subscription, bool $isRenewal): void
    {
        if (! config('subscription.admin_notifications_enabled', true)) {
            return;
        }

        User::query()
            ->where('role', UserRole::Admin)
            ->get()
            ->each(fn (User $admin) => $admin->notify(
                new SubscriptionPaymentConfirmedAdminNotification($subscription, $isRenewal),
            ));
    }

    /**
     * @return Collection<int, ProfessionalSubscription>
     */
    public function subscriptionsDueForReminder(): Collection
    {
        $soonDays = (int) config('subscription.expiring_soon_days', 3);
        $windowEnd = now()->addDays($soonDays)->endOfDay();

        return ProfessionalSubscription::query()
            ->with(['user', 'plan'])
            ->whereNull('cancelled_at')
            ->where(function ($query) use ($windowEnd) {
                $query->where(function ($trial) use ($windowEnd) {
                    $trial->where('status', SubscriptionStatus::Trialing)
                        ->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '>=', now()->startOfDay())
                        ->where('trial_ends_at', '<=', $windowEnd);
                })->orWhere(function ($active) use ($windowEnd) {
                    $active->where('status', SubscriptionStatus::Active)
                        ->whereNotNull('ends_at')
                        ->where('ends_at', '>=', now()->startOfDay())
                        ->where('ends_at', '<=', $windowEnd);
                });
            })
            ->get();
    }

    /**
     * @return array{expires_at: Carbon, days_remaining: int, is_trial: bool}|null
     */
    public function reminderContext(ProfessionalSubscription $subscription): ?array
    {
        $expiresAt = $this->resolveExpirationDate($subscription);
        if ($expiresAt === null || $expiresAt->isPast()) {
            return null;
        }

        $daysRemaining = (int) now()->startOfDay()->diffInDays($expiresAt->startOfDay(), false);
        if ($daysRemaining < 0) {
            return null;
        }

        return [
            'expires_at' => $expiresAt,
            'days_remaining' => $daysRemaining,
            'is_trial' => $subscription->status === SubscriptionStatus::Trialing,
        ];
    }

    public function expireDueSubscriptions(): int
    {
        $expired = 0;

        $expired += ProfessionalSubscription::query()
            ->where('status', SubscriptionStatus::Trialing)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->update(['status' => SubscriptionStatus::Expired]);

        $expired += ProfessionalSubscription::query()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::PastDue])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->update(['status' => SubscriptionStatus::Expired]);

        return $expired;
    }

    public function startTrial(User $user): ProfessionalSubscription
    {
        $plan = SubscriptionPlan::query()
            ->where('slug', SubscriptionPlanSlug::Trial)
            ->where('is_active', true)
            ->firstOrFail();

        $trialDays = (int) config('subscription.trial_days', 14);
        $startsAt = now();

        return ProfessionalSubscription::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => SubscriptionStatus::Trialing,
                'starts_at' => $startsAt,
                'ends_at' => null,
                'trial_ends_at' => $startsAt->copy()->addDays($trialDays),
                'gateway_external_id' => null,
                'cancelled_at' => null,
            ],
        );
    }

    /**
     * @return array{
     *   level: string,
     *   message: string|null,
     *   days_remaining: int|null,
     *   subscription: ProfessionalSubscription|null,
     *   patient_quota: array,
     *   cta_label: string|null
     * }
     */
    public function bannerContext(User $user): array
    {
        if ($this->isExempt($user) || ! $user->isProfessional()) {
            return $this->emptyBannerContext();
        }

        $subscription = $this->activeSubscription($user);

        if ($subscription === null) {
            return $this->finalizeBannerContext([
                'level' => 'danger',
                'message' => __('Não há assinatura activa. Renove para continuar a criar pacientes, sessões, prontuários e utilizar a IA.'),
                'days_remaining' => null,
                'subscription' => null,
            ], $user);
        }

        if (! $this->subscriptionIsCurrentlyValid($subscription)) {
            return $this->finalizeBannerContext([
                'level' => 'danger',
                'message' => __('A sua assinatura expirou. Pode consultar os dados existentes, mas novas acções estão bloqueadas.'),
                'days_remaining' => 0,
                'subscription' => $subscription,
            ], $user);
        }

        $expiresAt = $this->resolveExpirationDate($subscription);
        if ($expiresAt === null) {
            return $this->finalizeBannerContext([
                'level' => 'none',
                'message' => null,
                'days_remaining' => null,
                'subscription' => $subscription,
            ], $user);
        }

        $daysRemaining = (int) now()->startOfDay()->diffInDays($expiresAt->startOfDay(), false);

        if ($daysRemaining < 0) {
            return $this->finalizeBannerContext([
                'level' => 'danger',
                'message' => __('A sua assinatura expirou. Pode consultar os dados existentes, mas novas acções estão bloqueadas.'),
                'days_remaining' => 0,
                'subscription' => $subscription,
            ], $user);
        }

        $soonDays = (int) config('subscription.expiring_soon_days', 3);

        if ($daysRemaining <= $soonDays) {
            $message = $subscription->status === SubscriptionStatus::Trialing
                ? __('O seu período de teste termina em :days dia(s).', ['days' => $daysRemaining])
                : __('A sua assinatura termina em :days dia(s).', ['days' => $daysRemaining]);

            return $this->finalizeBannerContext([
                'level' => 'warning',
                'message' => $message,
                'days_remaining' => $daysRemaining,
                'subscription' => $subscription,
            ], $user);
        }

        return $this->finalizeBannerContext([
            'level' => 'none',
            'message' => null,
            'days_remaining' => $daysRemaining,
            'subscription' => $subscription,
        ], $user);
    }

    /**
     * @return array{
     *   level: string,
     *   message: string|null,
     *   days_remaining: int|null,
     *   subscription: ProfessionalSubscription|null,
     *   patient_quota: array,
     *   cta_label: string|null
     * }
     */
    private function emptyBannerContext(): array
    {
        return [
            'level' => 'none',
            'message' => null,
            'days_remaining' => null,
            'subscription' => null,
            'patient_quota' => $this->emptyPatientQuota(),
            'cta_label' => null,
        ];
    }

    /**
     * @param  array{
     *   level: string,
     *   message: string|null,
     *   days_remaining: int|null,
     *   subscription: ProfessionalSubscription|null
     * }  $context
     * @return array{
     *   level: string,
     *   message: string|null,
     *   days_remaining: int|null,
     *   subscription: ProfessionalSubscription|null,
     *   patient_quota: array,
     *   cta_label: string|null
     * }
     */
    private function finalizeBannerContext(array $context, User $user): array
    {
        $quota = $this->patientQuotaContext($user);
        $context['patient_quota'] = $quota;
        $context['cta_label'] = __('Renovar assinatura');

        if ($context['level'] === 'none') {
            if ($quota['at_limit']) {
                $context['level'] = 'danger';
                $context['message'] = __('Limite de pacientes atingido (:count/:limit). Actualize o plano para registar novos utentes.', [
                    'count' => $quota['count'],
                    'limit' => $quota['limit'],
                ]);
                $context['cta_label'] = __('Ver planos');
            } elseif ($quota['near_limit']) {
                $context['level'] = 'warning';
                $context['message'] = __('Quase no limite de pacientes (:count/:limit, :remaining restante(s)).', [
                    'count' => $quota['count'],
                    'limit' => $quota['limit'],
                    'remaining' => $quota['remaining'],
                ]);
                $context['cta_label'] = __('Ver planos');
            }
        }

        return $context;
    }

    /**
     * @return array{
     *   limited: bool,
     *   limit: int|null,
     *   count: int,
     *   remaining: int|null,
     *   at_limit: bool,
     *   near_limit: bool
     * }
     */
    private function emptyPatientQuota(): array
    {
        return [
            'limited' => false,
            'limit' => null,
            'count' => 0,
            'remaining' => null,
            'at_limit' => false,
            'near_limit' => false,
        ];
    }

    private function subscriptionIsCurrentlyValid(ProfessionalSubscription $subscription): bool
    {
        if (in_array($subscription->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired], true)) {
            return false;
        }

        if ($subscription->status === SubscriptionStatus::PastDue) {
            return false;
        }

        $expiresAt = $this->resolveExpirationDate($subscription);

        if ($subscription->cancelled_at !== null) {
            return $expiresAt !== null && now()->lte($expiresAt);
        }

        if ($expiresAt === null) {
            return in_array($subscription->status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true);
        }

        return now()->lte($expiresAt);
    }

    private function resolveExpirationDate(ProfessionalSubscription $subscription): ?Carbon
    {
        if ($subscription->status === SubscriptionStatus::Trialing) {
            return $subscription->trial_ends_at;
        }

        return $subscription->ends_at;
    }

    /**
     * @param  array{event: string, external_id: string|null, subscription_external_id: string|null, status: string|null, raw: array<string, mixed>}  $parsed
     */
    private function isOverdueWebhook(array $parsed): bool
    {
        if ($parsed['event'] === 'PAYMENT_OVERDUE') {
            return true;
        }

        return in_array($parsed['status'], ['OVERDUE'], true);
    }

    private function nextRenewalEndsAt(ProfessionalSubscription $subscription): Carbon
    {
        $billingCycle = BillingCycle::tryFrom((string) ($subscription->gateway_meta['billing_cycle'] ?? ''))
            ?? BillingCycle::Monthly;

        $base = $subscription->ends_at !== null && $subscription->ends_at->isFuture()
            ? $subscription->ends_at->copy()
            : now();

        return $billingCycle === BillingCycle::Yearly
            ? $base->addYear()
            : $base->addMonth();
    }

    private function cancelGatewaySubscriptionRecord(ProfessionalSubscription $subscription): void
    {
        if (! filled($subscription->gateway_external_id)) {
            return;
        }

        try {
            $this->gateway->cancelSubscription((string) $subscription->gateway_external_id);
        } catch (AsaasApiException) {
            // Permite upgrade local mesmo se o gateway já tiver cancelado.
        }
    }

    private function resolveAsaasCustomer(User $user): string
    {
        if (filled($user->asaas_customer_id)) {
            return (string) $user->asaas_customer_id;
        }

        $customerId = $this->gateway->ensureCustomer([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ? only_digits((string) $user->phone) : null,
            'external_reference' => 'user:'.$user->id,
        ]);

        $user->update(['asaas_customer_id' => $customerId]);

        return $customerId;
    }
}
