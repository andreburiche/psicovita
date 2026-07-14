@props(['session'])

@php
    $timeStr = is_string($session->session_time)
        ? substr($session->session_time, 0, 5)
        : $session->session_time->format('H:i');

    $sessionLabel = $session->displayLabel();

    $chipClasses = match ($session->status) {
        \App\Enums\TherapySessionStatus::Completed => 'bg-emerald-50 text-emerald-900 ring-emerald-600/15 dark:bg-emerald-950/50 dark:text-emerald-100 dark:ring-emerald-500/30',
        \App\Enums\TherapySessionStatus::Cancelled => 'bg-rose-50 text-rose-800 line-through ring-rose-600/15 dark:bg-rose-950/40 dark:text-rose-200 dark:ring-rose-500/30',
        default => 'bg-violet-50 text-violet-900 ring-violet-600/10 dark:bg-violet-950/50 dark:text-violet-100 dark:ring-violet-500/30',
    };

    $isScheduled = $session->status === \App\Enums\TherapySessionStatus::Scheduled;
@endphp

<li class="pointer-events-auto relative">
    <div class="relative rounded-lg px-1 py-0.5 text-[10px] font-medium ring-1 transition sm:text-xs {{ $chipClasses }}">
        @if ($isScheduled)
            <div
                x-data="{
                    open: false,
                    top: 0,
                    left: 0,
                    place() {
                        const el = this.$refs.trigger;
                        if (! el) return;
                        const rect = el.getBoundingClientRect();
                        this.top = rect.bottom + 4;
                        this.left = rect.left;
                    },
                    toggle() {
                        this.open = ! this.open;
                        if (this.open) {
                            this.$nextTick(() => this.place());
                        }
                    },
                    close() {
                        this.open = false;
                    },
                }"
                @keydown.escape.window="close()"
                @scroll.window="if (open) place()"
                @resize.window="if (open) place()"
            >
                <button
                    type="button"
                    x-ref="trigger"
                    class="relative z-10 block w-full truncate text-left transition hover:opacity-80"
                    title="{{ $sessionLabel }} · {{ $session->status->label() }}"
                    aria-haspopup="menu"
                    x-bind:aria-expanded="open"
                    @click.stop="toggle()"
                >
                    {{ $timeStr }} {{ \Illuminate\Support\Str::limit($sessionLabel, 10) }}
                </button>

                <template x-teleport="body">
                    <div
                        x-show="open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        @click.outside="close()"
                        x-bind:style="{ position: 'fixed', top: top + 'px', left: left + 'px', zIndex: 9999 }"
                        class="w-max min-w-[10.5rem] max-w-[14rem]"
                        role="menu"
                        aria-label="{{ __('Ações da sessão') }}"
                    >
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:shadow-black/50 dark:ring-slate-600">
                            <p class="bg-white px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                {{ __('Marcar como') }}
                            </p>

                            <form method="post" action="{{ route('therapy-sessions.update-status', $session) }}" class="contents">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="status" value="{{ \App\Enums\TherapySessionStatus::Completed->value }}" />
                                <button
                                    type="submit"
                                    role="menuitem"
                                    class="flex w-full items-center gap-2 rounded-lg bg-white px-2 py-1.5 text-left text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:bg-slate-800 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                                >
                                    <x-ui.icon name="check" class="h-3.5 w-3.5 shrink-0" />
                                    {{ __('Concluída') }}
                                </button>
                            </form>

                            <form method="post" action="{{ route('therapy-sessions.update-status', $session) }}" class="contents">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="status" value="{{ \App\Enums\TherapySessionStatus::Cancelled->value }}" />
                                <button
                                    type="submit"
                                    role="menuitem"
                                    class="flex w-full items-center gap-2 rounded-lg bg-white px-2 py-1.5 text-left text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:bg-slate-800 dark:text-rose-300 dark:hover:bg-rose-950/50"
                                >
                                    <x-ui.icon name="ban" class="h-3.5 w-3.5 shrink-0" />
                                    {{ __('Cancelada') }}
                                </button>
                            </form>

                            <div class="my-1 border-t border-slate-200 dark:border-slate-600"></div>

                            <a
                                href="{{ route('therapy-sessions.show', $session) }}"
                                role="menuitem"
                                class="flex items-center gap-2 rounded-lg bg-white px-2 py-1.5 text-xs font-semibold text-violet-600 transition hover:bg-violet-50 dark:bg-slate-800 dark:text-violet-400 dark:hover:bg-violet-950/40"
                            >
                                <x-ui.icon name="eye" class="h-3.5 w-3.5 shrink-0" />
                                {{ __('Ver detalhes') }}
                            </a>

                            <a
                                href="{{ route('therapy-sessions.video.room', $session) }}"
                                role="menuitem"
                                class="flex items-center gap-2 rounded-lg bg-white px-2 py-1.5 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-50 dark:bg-slate-800 dark:text-indigo-400 dark:hover:bg-indigo-950/40"
                            >
                                <x-ui.icon name="video" class="h-3.5 w-3.5 shrink-0" />
                                {{ __('Videoconferência') }}
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        @else
            <a
                href="{{ route('therapy-sessions.show', $session) }}"
                class="relative z-10 block truncate transition hover:opacity-80"
                title="{{ $sessionLabel }} · {{ $session->status->label() }}"
            >
                {{ $timeStr }} {{ \Illuminate\Support\Str::limit($sessionLabel, 10) }}
            </a>
        @endif
    </div>
</li>
