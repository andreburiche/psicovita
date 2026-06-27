@php
    $user = auth()->user();
    $linkSent = session('status') === 'verification-link-sent';
@endphp

<x-auth-split-layout
    :heading="__('Confirme o seu e-mail')"
    :description="__('Falta um passo para ativar a sua conta e aceder à plataforma com segurança.')"
    :promote-register="false"
    :promote-login="false"
>
    <div class="space-y-6" data-test="verify-email-page">
        @if ($linkSent)
            <div
                class="flex items-start gap-3 rounded-2xl border border-emerald-200/90 bg-gradient-to-br from-emerald-50/95 via-white to-teal-50/60 px-4 py-4 text-sm shadow-sm ring-1 ring-emerald-100 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:via-slate-900/80 dark:to-teal-950/20 dark:ring-emerald-900/30"
                role="status"
                data-test="verification-link-sent"
            >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400" aria-hidden="true">
                    <x-ui.icon name="check-badge" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <p class="font-semibold text-emerald-900 dark:text-emerald-100">{{ __('Novo e-mail enviado') }}</p>
                    <p class="mt-1 leading-relaxed text-emerald-800/90 dark:text-emerald-200/90">
                        {{ __('Enviámos outra ligação de confirmação para :email. Verifique a caixa de entrada e o spam.', ['email' => $user->email]) }}
                    </p>
                </div>
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-violet-200/80 bg-gradient-to-br from-violet-50/80 via-white to-indigo-50/50 p-5 shadow-sm ring-1 ring-violet-100 dark:border-violet-900/40 dark:from-violet-950/30 dark:via-slate-900/80 dark:to-indigo-950/20 dark:ring-violet-900/30">
            <div class="flex items-start gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-lg shadow-violet-500/25" aria-hidden="true">
                    <x-ui.icon name="mail" class="h-7 w-7" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-violet-700 dark:text-violet-300">{{ __('Enviámos um e-mail para') }}</p>
                    <p class="mt-1 break-all text-lg font-bold tracking-tight text-slate-900 dark:text-white">{{ $user->email }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ __('Clique na ligação de confirmação para concluir o registo. Só depois poderá usar todas as funcionalidades da conta.') }}
                    </p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
            <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300" aria-hidden="true">
                    <x-ui.icon name="clipboard" class="h-4 w-4" />
                </span>
                {{ __('Como confirmar') }}
            </h3>

            <ol class="mt-4 space-y-3">
                @foreach ([
                    __('Abra a caixa de entrada do e-mail indicado acima.'),
                    __('Procure a mensagem de confirmação enviada pelo :app.', ['app' => config('app.name')]),
                    __('Clique no botão «Confirmar e-mail» — será redirecionado automaticamente.'),
                ] as $index => $step)
                    <li class="flex gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-300">
                            {{ $index + 1 }}
                        </span>
                        <span class="pt-0.5 leading-relaxed">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="rounded-xl border border-amber-200/90 bg-amber-50/70 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
            <p class="flex items-start gap-2.5">
                <x-ui.icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                <span class="leading-relaxed">
                    {{ __('Não encontrou o e-mail? Verifique a pasta de spam ou lixo eletrónico. A ligação expira em 60 minutos.') }}
                </span>
            </p>
        </div>

        <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
            @csrf

            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-slate-900"
            >
                <x-ui.icon name="mail" class="h-4 w-4" />
                {{ $linkSent ? __('Enviar novamente') : __('Reenviar e-mail de confirmação') }}
            </button>

            <p class="text-center text-xs text-slate-500 dark:text-slate-400">
                {{ __('Pode solicitar um novo envio se o e-mail anterior não chegou ou expirou.') }}
            </p>
        </form>

        <div class="flex items-center justify-center border-t border-slate-100 pt-5 dark:border-slate-700">
            <x-logout-button
                variant="inline"
                class="!border-slate-200 !bg-transparent !text-slate-600 !shadow-none hover:!border-slate-300 hover:!bg-slate-50 hover:!text-slate-800 dark:!border-slate-600 dark:!text-slate-400 dark:hover:!bg-slate-800/60 dark:hover:!text-slate-200"
            >
                <span class="inline-flex items-center gap-2">
                    <x-ui.icon name="log-in" class="h-4 w-4 rotate-180" />
                    {{ __('Terminar sessão e usar outra conta') }}
                </span>
            </x-logout-button>
        </div>
    </div>
</x-auth-split-layout>
