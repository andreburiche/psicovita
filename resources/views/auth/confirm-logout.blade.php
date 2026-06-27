@php
    $user = auth()->user();
@endphp

<x-auth-split-layout
    :heading="__('A terminar sessão')"
    :description="__('Estamos a encerrar a sua sessão de forma segura. Aguarde um momento.')"
    :promote-register="false"
    :promote-login="false"
>
    <div
        class="space-y-6 text-center"
        data-test="confirm-logout-page"
        x-data="{ submitting: false }"
        x-init="
            submitting = true;
            $nextTick(() => document.getElementById('logout-form')?.requestSubmit());
        "
    >
        <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-lg shadow-violet-500/25" aria-hidden="true">
                <x-ui.icon name="log-in" class="h-8 w-8 rotate-180" />
            </div>

            <div class="mt-5 flex items-center justify-center gap-3" aria-live="polite">
                <span
                    class="inline-flex h-5 w-5 animate-spin rounded-full border-2 border-violet-200 border-t-violet-600 dark:border-violet-800 dark:border-t-violet-400"
                    x-show="submitting"
                    aria-hidden="true"
                ></span>
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="submitting ? @js(__('A encerrar sessão…')) : @js(__('Pronto para sair'))"></p>
            </div>

            @if ($user)
                <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $user->name }}</p>
                    <p class="mt-0.5 break-all text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                </div>
            @endif

            <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                {{ __('Se o encerramento não iniciar automaticamente, utilize o botão abaixo.') }}
            </p>
        </section>

        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="space-y-4">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                @click="submitting = true"
            >
                <x-ui.icon name="log-in" class="h-4 w-4 rotate-180" />
                {{ __('Sair agora') }}
            </button>
        </form>

        <noscript>
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                {{ __('O JavaScript está desativado. Clique em «Sair agora» para terminar a sessão.') }}
            </p>
        </noscript>

        @if ($user)
            <div class="border-t border-slate-100 pt-5 dark:border-slate-700">
                <a
                    href="{{ route($user->defaultAppRouteName()) }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-violet-600 dark:text-slate-400 dark:hover:text-violet-400"
                >
                    <x-ui.icon name="arrow-left" class="h-4 w-4" />
                    {{ __('Cancelar e voltar à aplicação') }}
                </a>
            </div>
        @endif
    </div>
</x-auth-split-layout>
