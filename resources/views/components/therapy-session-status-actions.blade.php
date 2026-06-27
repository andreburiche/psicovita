@props(['session', 'compact' => false])

@php
    use App\Enums\TherapySessionStatus;
@endphp

@if ($session->status === TherapySessionStatus::Scheduled)
    <div
        {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-end gap-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100']) }}
        role="group"
        aria-label="{{ __('Atualizar status') }}"
    >
        <form method="post" action="{{ route('therapy-sessions.update-status', $session) }}">
            @csrf
            @method('patch')
            <input type="hidden" name="status" value="{{ TherapySessionStatus::Completed->value }}" />
            <button
                type="submit"
                title="{{ __('Marcar como concluída') }}"
                class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/40"
            >
                <x-ui.icon name="check" class="h-3.5 w-3.5 shrink-0" />
                @unless ($compact)
                    <span>{{ __('Concluída') }}</span>
                @endunless
            </button>
        </form>

        <form method="post" action="{{ route('therapy-sessions.update-status', $session) }}">
            @csrf
            @method('patch')
            <input type="hidden" name="status" value="{{ TherapySessionStatus::Cancelled->value }}" />
            <button
                type="submit"
                title="{{ __('Marcar como cancelada') }}"
                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-900/40"
            >
                <x-ui.icon name="ban" class="h-3.5 w-3.5 shrink-0" />
                @unless ($compact)
                    <span>{{ __('Cancelada') }}</span>
                @endunless
            </button>
        </form>
    </div>
@else
    <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('—') }}</span>
@endif
