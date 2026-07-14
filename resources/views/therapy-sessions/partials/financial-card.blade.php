@php
    $billing = $billingOverview ?? [];
    $lines = $billing['lines'] ?? [];
    $hasLines = count($lines) > 0;
    $isMulti = (bool) ($billing['is_multi_participant'] ?? false);
    $missingCount = (int) ($billing['missing_count'] ?? 0);
    $billingService = app(\App\Services\SessionBillingService::class);
@endphp

<div class="space-y-4 p-5">
    @if ($hasLines || ($billing['total_participants'] ?? 0) > 0)
        @if ($isMulti || count($lines) > 1)
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ __('Resumo da sessão') }}</p>
                <x-ui.badge :variant="$billing['aggregate_variant'] ?? 'neutral'">{{ $billing['aggregate_label'] ?? __('Em aberto') }}</x-ui.badge>
            </div>
        @endif

        <ul class="space-y-3">
            @foreach ($lines as $line)
                @php
                    $payment = $line['payment'] ?? null;
                    $participant = $line['participant'] ?? null;
                    $lineBadge = $payment
                        ? $billingService->badgeVariantForPayment($payment)
                        : 'neutral';
                    $lineStatus = $payment?->status?->label() ?? __('Sem cobrança');
                @endphp
                <li class="rounded-xl border border-slate-200/80 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/60">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $line['label'] }}</p>
                            @if (! empty($line['email']))
                                <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $line['email'] }}</p>
                            @endif
                            @if (! empty($line['role_label']))
                                <p class="mt-1 text-xs text-violet-600 dark:text-violet-400">{{ $line['role_label'] }}</p>
                            @endif
                        </div>
                        <x-ui.badge :variant="$lineBadge">{{ $lineStatus }}</x-ui.badge>
                    </div>

                    @if ($payment)
                        <p class="mt-3 text-lg font-bold text-slate-900 dark:text-white">
                            R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}
                        </p>
                        <a
                            href="{{ route('payments.show', $payment) }}"
                            class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 transition hover:text-emerald-600 dark:text-emerald-400"
                        >
                            {{ __('Ver pagamento') }}
                            <span aria-hidden="true">→</span>
                        </a>
                    @else
                        <a
                            href="{{ route('payments.create', array_filter([
                                'therapy_session_id' => $session->id,
                                'session_participant_id' => $participant?->id,
                                'patient_id' => $session->patient_id,
                            ])) }}"
                            class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-amber-700 transition hover:text-amber-600 dark:text-amber-300"
                        >
                            <x-ui.icon name="plus" class="h-3.5 w-3.5" />
                            {{ __('Registar cobrança') }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($missingCount > 0)
            <a
                href="{{ route('payments.create', ['therapy_session_id' => $session->id]) }}"
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200 dark:hover:bg-emerald-950/50"
            >
                <x-ui.icon name="plus" class="h-4 w-4 shrink-0" />
                {{ __('Registar outra cobrança') }}
            </a>
        @endif
    @else
        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Nenhum pagamento associado a esta sessão.') }}</p>
        @if ($session->session_mode === \App\Enums\SessionMode::Group)
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Em sessões de grupo, cada participante pode ter a sua própria cobrança.') }}</p>
        @endif
        @if (($observers ?? collect())->isNotEmpty() || ($familyGuests ?? collect())->isNotEmpty())
            <p class="text-xs text-amber-700 dark:text-amber-300">{{ __('Selecione qual participante do evento será cobrado.') }}</p>
        @endif
        <a
            href="{{ route('payments.create', array_filter([
                'therapy_session_id' => $session->id,
                'patient_id' => $session->patient_id,
            ])) }}"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-500/20 transition hover:from-emerald-500 hover:to-teal-500"
        >
            <x-ui.icon name="plus" class="h-4 w-4 shrink-0" />
            {{ __('Registar pagamento') }}
        </a>
    @endif
</div>
