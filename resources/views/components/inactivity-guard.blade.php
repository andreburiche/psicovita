@auth
    @php
        $timeoutMinutes = auth()->user()->inactivityTimeoutMinutes();
        $warningSeconds = max(1, (int) config('security.inactivity_warning_seconds', 60));
    @endphp

    {{-- Não usar display:contents no root do Alpine: em vários browsers quebra x-show do modal. --}}
    <div
        x-data="inactivityGuard(
            {{ (int) $timeoutMinutes }},
            {{ (int) $warningSeconds }},
            @js(route('session.keep-alive')),
            @js(route('session.inactivity-expire'))
        )"
    >
        <div
            x-show="showWarning"
            x-on:keydown.escape.window="showWarning ? keepAlive() : null"
            class="fixed inset-0 z-[70] overflow-y-auto px-4 py-6 sm:px-0"
            role="dialog"
            aria-modal="true"
            aria-labelledby="inactivity-warning-title"
            style="display: none;"
        >
            <div
                x-show="showWarning"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm dark:bg-black/70"
                aria-hidden="true"
            ></div>

            <div class="flex min-h-full items-center justify-center">
                <div
                    x-show="showWarning"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700"
                    x-on:click.stop
                >
                    <div class="px-6 py-5 text-white" style="background: linear-gradient(to right, #f59e0b, #f97316);">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-100">{{ __('Sessão') }}</p>
                        <h2 id="inactivity-warning-title" class="mt-1 text-xl font-semibold tracking-tight">
                            {{ __('Sua sessão vai expirar') }}
                        </h2>
                        <p class="mt-2 text-sm text-amber-50/95">
                            {{ __('Por inatividade, você será desconectado em breve.') }}
                        </p>
                    </div>

                    <div class="space-y-5 bg-white p-6 dark:bg-slate-900">
                        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('Por inatividade, você será desconectado em') }}
                            <span class="font-semibold tabular-nums text-slate-900 dark:text-white" x-text="countdown"></span>
                            {{ __('segundos.') }}
                        </p>

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                                x-on:click="logout()"
                            >
                                {{ __('Sair agora') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-emerald-600/25 transition hover:bg-emerald-500"
                                x-on:click="keepAlive()"
                            >
                                {{ __('Continuar conectado') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endauth
