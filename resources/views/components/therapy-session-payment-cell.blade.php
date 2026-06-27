@props(['session'])

@php
    $billing = app(\App\Services\SessionBillingService::class)->overview($session);
    $lines = $billing['lines'] ?? [];
    $singlePayment = count($lines) === 1 ? ($lines[0]['payment'] ?? null) : null;
@endphp

@if ($billing['is_multi_participant'] || count($lines) > 1)
    <a
        href="{{ route('therapy-sessions.show', $session) }}#financeiro"
        class="inline-flex flex-col gap-0.5 transition hover:opacity-80"
        title="{{ __('Ver cobranças da sessão') }}"
    >
        <x-ui.badge :variant="$billing['aggregate_variant'] ?? 'neutral'">{{ $billing['aggregate_label'] ?? __('Em aberto') }}</x-ui.badge>
        @if (($billing['paid_count'] ?? 0) > 0)
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ __(':paid de :total pagos', ['paid' => $billing['paid_count'], 'total' => max($billing['total_participants'], 1)]) }}
            </span>
        @endif
    </a>
@elseif ($singlePayment)
    <a
        href="{{ route('payments.show', $singlePayment) }}"
        class="inline-flex flex-col gap-0.5 transition hover:opacity-80"
        title="{{ __('Ver pagamento') }}"
    >
        <x-ui.badge :variant="app(\App\Services\SessionBillingService::class)->badgeVariantForPayment($singlePayment)">{{ $singlePayment->status->label() }}</x-ui.badge>
        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">
            R$ {{ number_format((float) $singlePayment->amount, 2, ',', '.') }}
        </span>
    </a>
@else
    <a
        href="{{ route('payments.create', array_filter([
            'therapy_session_id' => $session->id,
            'patient_id' => $session->patient_id,
        ])) }}"
        class="text-xs font-semibold text-emerald-700 transition hover:text-emerald-600 dark:text-emerald-400 dark:hover:text-emerald-300"
    >
        {{ __('Registar') }}
    </a>
@endif
