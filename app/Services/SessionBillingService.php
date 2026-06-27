<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SessionMode;
use App\Models\Payment;
use App\Models\SessionParticipant;
use App\Models\TherapySession;
use Illuminate\Support\Collection;

class SessionBillingService
{
    public function __construct(
        private readonly SessionParticipantService $participants,
    ) {}

    /**
     * @return array{
     *     lines: list<array{participant: ?SessionParticipant, payment: ?Payment, label: string, email: ?string, role_label: ?string}>,
     *     paid_count: int,
     *     pending_count: int,
     *     missing_count: int,
     *     total_participants: int,
     *     is_multi_participant: bool,
     *     all_paid: bool,
     *     has_partial: bool,
     *     aggregate_label: string,
     *     aggregate_variant: string,
     * }
     */
    public function overview(TherapySession $session): array
    {
        $session->loadMissing(['payments.patient', 'patient']);
        $payments = $session->payments;
        $billable = $this->participants->billableParticipants($session);
        $isMultiParticipant = $billable->count() > 1
            || in_array($session->session_mode, [SessionMode::WithObserver, SessionMode::Family, SessionMode::Group], true);

        $usedPaymentIds = [];
        $lines = [];

        if ($billable->isNotEmpty()) {
            foreach ($billable as $participant) {
                $payment = $this->matchPaymentToParticipant($payments, $participant, $usedPaymentIds);
                if ($payment !== null) {
                    $usedPaymentIds[] = $payment->id;
                }

                $lines[] = [
                    'participant' => $participant,
                    'payment' => $payment,
                    'label' => (string) $participant->display_name,
                    'email' => $participant->email,
                    'role_label' => $this->participants->participantBillingLabel($participant),
                ];
            }
        } elseif ($session->patient_id) {
            $payment = $payments->firstWhere('patient_id', $session->patient_id) ?? $payments->first();
            if ($payment !== null) {
                $usedPaymentIds[] = $payment->id;
            }

            $lines[] = [
                'participant' => null,
                'payment' => $payment,
                'label' => (string) ($session->patient?->name ?? __('Utente')),
                'email' => $session->patient?->email,
                'role_label' => null,
            ];
        }

        foreach ($payments as $payment) {
            if (in_array($payment->id, $usedPaymentIds, true)) {
                continue;
            }

            $lines[] = [
                'participant' => null,
                'payment' => $payment,
                'label' => (string) ($payment->patient?->name ?? __('Cobrança')),
                'email' => $payment->patient?->email,
                'role_label' => null,
            ];
        }

        $paidCount = collect($lines)->filter(
            fn (array $line) => $line['payment']?->status === PaymentStatus::Paid
        )->count();
        $pendingCount = collect($lines)->filter(function (array $line) {
            $status = $line['payment']?->status;

            return $status === PaymentStatus::Pending || $status === PaymentStatus::Overdue;
        })->count();
        $missingCount = collect($lines)->filter(fn (array $line) => $line['payment'] === null)->count();

        $totalParticipants = $billable->count();
        if ($totalParticipants === 0 && $session->patient_id) {
            $totalParticipants = 1;
        }
        if ($totalParticipants === 0 && $payments->isNotEmpty()) {
            $totalParticipants = $payments->count();
        }

        $allPaid = $totalParticipants > 0
            && $paidCount === $totalParticipants
            && $missingCount === 0
            && $pendingCount === 0;
        $hasPartial = ($paidCount > 0 && ! $allPaid)
            || $missingCount > 0
            || ($pendingCount > 0 && $paidCount > 0);

        [$aggregateLabel, $aggregateVariant] = $this->aggregateStatus(
            $payments,
            $paidCount,
            $pendingCount,
            $missingCount,
            $totalParticipants,
            $allPaid,
            $hasPartial,
            $isMultiParticipant,
        );

        return [
            'lines' => $lines,
            'paid_count' => $paidCount,
            'pending_count' => $pendingCount,
            'missing_count' => $missingCount,
            'total_participants' => $totalParticipants,
            'is_multi_participant' => $isMultiParticipant,
            'all_paid' => $allPaid,
            'has_partial' => $hasPartial,
            'aggregate_label' => $aggregateLabel,
            'aggregate_variant' => $aggregateVariant,
        ];
    }

    public function badgeVariantForPayment(?Payment $payment): string
    {
        if ($payment === null) {
            return 'neutral';
        }

        return match ($payment->status) {
            PaymentStatus::Paid => 'success',
            PaymentStatus::Pending, PaymentStatus::Overdue => 'warning',
            PaymentStatus::Cancelled => 'neutral',
            default => 'neutral',
        };
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @param  list<int>  $usedPaymentIds
     */
    private function matchPaymentToParticipant(
        Collection $payments,
        SessionParticipant $participant,
        array $usedPaymentIds,
    ): ?Payment {
        foreach ($payments as $payment) {
            if (in_array($payment->id, $usedPaymentIds, true)) {
                continue;
            }

            if ($participant->patient_id && (int) $payment->patient_id === (int) $participant->patient_id) {
                return $payment;
            }

            $participantEmail = strtolower(trim((string) $participant->email));
            $paymentEmail = strtolower(trim((string) ($payment->patient?->email ?? '')));

            if ($participantEmail !== '' && $participantEmail === $paymentEmail) {
                return $payment;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return array{0: string, 1: string}
     */
    private function aggregateStatus(
        Collection $payments,
        int $paidCount,
        int $pendingCount,
        int $missingCount,
        int $totalParticipants,
        bool $allPaid,
        bool $hasPartial,
        bool $isMultiParticipant,
    ): array {
        if ($payments->isEmpty() && $missingCount > 0) {
            return [__('Sem cobrança'), 'neutral'];
        }

        if ($allPaid) {
            return [__('Tudo pago'), 'success'];
        }

        if ($isMultiParticipant && $hasPartial) {
            return [
                __('Parcial (:paid/:total)', [
                    'paid' => $paidCount,
                    'total' => max($totalParticipants, 1),
                ]),
                'warning',
            ];
        }

        if ($payments->count() === 1) {
            $status = $payments->first()->status;

            return [
                $status->label(),
                match ($status) {
                    PaymentStatus::Paid => 'success',
                    PaymentStatus::Pending, PaymentStatus::Overdue => 'warning',
                    default => 'neutral',
                },
            ];
        }

        if ($pendingCount > 0 && $paidCount === 0) {
            return [__('Pendente'), 'warning'];
        }

        return [__('Em aberto'), 'warning'];
    }
}
