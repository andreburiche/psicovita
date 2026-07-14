@props(['session'])

@php
    $billing = app(\App\Services\SessionBillingService::class)->overview($session);
    $lines = $billing['lines'] ?? [];
    $singlePayment = count($lines) === 1 ? ($lines[0]['payment'] ?? null) : null;

    if ($billing['is_multi_participant'] || count($lines) > 1) {
        $paymentUrl = route('therapy-sessions.show', $session).'#financeiro';
        $paymentActionLabel = __('Ver cobranças');
    } elseif ($singlePayment) {
        $paymentUrl = route('payments.show', $singlePayment);
        $paymentActionLabel = __('Ver pagamento');
    } else {
        $paymentUrl = $session->patient_id
            ? route('payments.create', ['therapy_session_id' => $session->id, 'patient_id' => $session->patient_id])
            : route('payments.create', ['therapy_session_id' => $session->id]);
        $paymentActionLabel = __('Registar pagamento');
    }

    $isOnline = $session->type === \App\Enums\TherapySessionType::Online;
    $isScheduled = $session->status === \App\Enums\TherapySessionStatus::Scheduled;
@endphp

<x-dropdown align="right" width="56" contentClasses="py-1 rounded-xl bg-white shadow-lg ring-1 ring-slate-200/80 dark:bg-slate-800 dark:ring-slate-600 min-w-[12rem]">
    <x-slot name="trigger">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-violet-700 dark:hover:bg-violet-950/40 dark:hover:text-violet-300"
            aria-label="{{ __('Ações da sessão') }}"
        >
            <x-ui.icon name="menu" class="h-4 w-4 shrink-0" />
            <span class="hidden sm:inline">{{ __('Ações') }}</span>
        </button>
    </x-slot>

    <x-slot name="content">
        <x-dropdown-link :href="route('therapy-sessions.show', $session)">
            {{ __('Abrir sessão') }}
        </x-dropdown-link>

        <x-dropdown-link :href="route('therapy-sessions.edit', $session)">
            {{ __('Editar') }}
        </x-dropdown-link>

        <x-dropdown-link :href="$paymentUrl">
            <span class="flex items-center gap-2">
                <x-ui.icon name="currency" class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                {{ $paymentActionLabel }}
            </span>
        </x-dropdown-link>

        @if ($isOnline && $session->status !== \App\Enums\TherapySessionStatus::Cancelled)
            <x-dropdown-link :href="route('therapy-sessions.video.room', $session)">
                {{ __('Videoconferência') }}
            </x-dropdown-link>
        @endif

        @if ($isScheduled)
            <div class="my-1 border-t border-slate-100 dark:border-slate-700"></div>

            <form method="post" action="{{ route('therapy-sessions.update-status', $session) }}">
                @csrf
                @method('patch')
                <input type="hidden" name="status" value="{{ \App\Enums\TherapySessionStatus::Completed->value }}" />
                <button
                    type="submit"
                    class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-emerald-700 transition hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950/40"
                    @click="open = false"
                >
                    <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
                    {{ __('Marcar concluída') }}
                </button>
            </form>

            <form method="post" action="{{ route('therapy-sessions.update-status', $session) }}">
                @csrf
                @method('patch')
                <input type="hidden" name="status" value="{{ \App\Enums\TherapySessionStatus::Cancelled->value }}" />
                <button
                    type="submit"
                    class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-rose-700 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/40"
                    @click="open = false"
                >
                    <x-ui.icon name="ban" class="h-4 w-4 shrink-0" />
                    {{ __('Marcar cancelada') }}
                </button>
            </form>
        @endif
    </x-slot>
</x-dropdown>
