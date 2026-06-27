@props(['patient'])

@php
    use App\Enums\TherapySessionStatus;
    use App\Enums\TherapySessionType;

    $sessions = $patient->therapySessions;
    $sessionCount = $sessions->count();

    $statusVariant = fn (TherapySessionStatus $status) => match ($status) {
        TherapySessionStatus::Completed => 'success',
        TherapySessionStatus::Scheduled => 'info',
        TherapySessionStatus::Cancelled => 'danger',
    };

    $nextSession = $sessions
        ->filter(fn ($s) => $s->status === TherapySessionStatus::Scheduled && $s->session_date->gte(now()->startOfDay()))
        ->sortBy(fn ($s) => $s->session_date->format('Y-m-d').' '.$s->session_time)
        ->first();
@endphp

<section
    id="sessoes-recentes"
    class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60"
    aria-label="{{ __('Histórico de sessões') }}"
>
    <div class="flex flex-col gap-4 border-b border-slate-100 bg-gradient-to-r from-indigo-50/80 to-violet-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:from-indigo-950/40 dark:to-violet-950/30">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-200">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/15 dark:text-indigo-300" aria-hidden="true">
                        <x-ui.icon name="clock" class="h-4 w-4" />
                    </span>
                    {{ __('Sessões recentes') }}
                </h3>
                @if ($sessionCount > 0)
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                        {{ trans_choice(':count sessão|:count sessões', $sessionCount, ['count' => $sessionCount]) }}
                    </span>
                @endif
            </div>
            @if ($nextSession)
                <p class="mt-1.5 text-xs text-slate-600 dark:text-slate-400">
                    {{ __('Próxima:') }}
                    <span class="font-semibold text-slate-800 dark:text-slate-200">
                        {{ $nextSession->session_date->format('d/m/Y') }}
                        · {{ \Illuminate\Support\Str::of($nextSession->session_time)->substr(0, 5) }}
                    </span>
                </p>
            @endif
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a
                href="{{ route('therapy-sessions.create', ['patient_id' => $patient->id]) }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:bg-indigo-500 dark:hover:bg-indigo-400"
            >
                <x-ui.icon name="plus" class="h-3.5 w-3.5" />
                {{ __('Agendar') }}
            </a>
            <a
                href="{{ route('schedule.index') }}"
                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-indigo-700 transition hover:border-indigo-200 hover:bg-indigo-50 dark:border-slate-600 dark:bg-slate-800 dark:text-indigo-300 dark:hover:bg-slate-700"
            >{{ __('Ver agenda') }} →</a>
        </div>
    </div>

    <div class="flex-1 p-4 sm:p-5">
        <ul class="space-y-3" role="list">
            @forelse ($sessions as $session)
                @php
                    $isNext = $nextSession && $nextSession->id === $session->id;
                    $timeLabel = \Illuminate\Support\Str::of($session->session_time)->substr(0, 5);
                @endphp
                <li>
                    <a
                        href="{{ route('therapy-sessions.show', $session) }}"
                        @class([
                            'group block rounded-xl border p-4 transition focus:outline-none focus:ring-2 focus:ring-violet-500/30',
                            'border-violet-300 bg-gradient-to-br from-violet-50/90 to-indigo-50/50 shadow-sm ring-1 ring-violet-200/60 hover:border-violet-400 hover:shadow-md dark:border-violet-700/60 dark:from-violet-950/40 dark:to-indigo-950/30 dark:ring-violet-800/40' => $isNext,
                            'border-slate-200/80 bg-gradient-to-br from-white to-slate-50/50 hover:border-violet-200 hover:shadow-md dark:border-slate-600 dark:from-slate-800/80 dark:to-slate-900/50 dark:hover:border-violet-600' => ! $isNext,
                        ])
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <span
                                    @class([
                                        'relative flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-xl text-[10px] font-bold leading-none ring-1 transition',
                                        'bg-violet-100 text-violet-800 ring-violet-200 group-hover:bg-violet-200 dark:bg-violet-950 dark:text-violet-200 dark:ring-violet-800' => $isNext,
                                        'bg-slate-100 text-slate-600 ring-slate-200/80 group-hover:bg-violet-100 group-hover:text-violet-700 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600 dark:group-hover:bg-violet-950 dark:group-hover:text-violet-300' => ! $isNext,
                                    ])
                                    aria-hidden="true"
                                >
                                    <span class="text-[9px] uppercase tracking-wide opacity-80">{{ $session->session_date->translatedFormat('M') }}</span>
                                    <span class="text-sm">{{ $session->session_date->format('d') }}</span>
                                </span>
                                <div class="min-w-0 pt-0.5">
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ $session->session_date->format('d/m/Y') }}
                                        <span class="font-normal text-slate-500 dark:text-slate-400">· {{ $timeLabel }}</span>
                                    </p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <x-ui.badge :variant="$statusVariant($session->status)">
                                            {{ $session->status->label() }}
                                        </x-ui.badge>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                            @if ($session->type === TherapySessionType::Online)
                                                <x-ui.icon name="video" class="h-3.5 w-3.5" />
                                            @else
                                                <x-ui.icon name="map-pin" class="h-3.5 w-3.5" />
                                            @endif
                                            {{ $session->type->label() }}
                                        </span>
                                        @if ($isNext)
                                            <span class="inline-flex items-center rounded-full bg-violet-600/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-700 dark:bg-violet-400/15 dark:text-violet-300">
                                                {{ __('Próxima') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1 self-center text-sm font-semibold text-violet-600 opacity-80 transition group-hover:opacity-100 dark:text-violet-400">
                                <span class="hidden sm:inline">{{ __('Abrir') }}</span>
                                <x-ui.icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </div>
                    </a>
                </li>
            @empty
                <li class="rounded-xl border border-dashed border-indigo-200/70 bg-indigo-50/40 px-6 py-12 text-center dark:border-indigo-900/50 dark:bg-indigo-950/20">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400" aria-hidden="true">
                        <x-ui.icon name="calendar" class="h-6 w-6" />
                    </span>
                    <p class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Nenhuma sessão registrada') }}</p>
                    <p class="mx-auto mt-1.5 max-w-xs text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        {{ __('Agende a primeira sessão deste utente para acompanhar o histórico clínico.') }}
                    </p>
                    <a
                        href="{{ route('therapy-sessions.create', ['patient_id' => $patient->id]) }}"
                        class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                    >
                        <x-ui.icon name="plus" class="h-4 w-4" />
                        {{ __('Agendar sessão') }}
                    </a>
                </li>
            @endforelse
        </ul>
    </div>
</section>
