<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ $code }} — {{ $title }} | {{ config('app.name', 'PsicoVita') }}</title>

        @include('partials.fonts')
        @include('layouts.partials.theme-init')
        @include('layouts.partials.ui-accent-init')
        @include('partials.head-vite')
    </head>
    <body class="psi-app-background min-h-full font-sans text-slate-900 antialiased dark:text-slate-100">
        <x-theme-toggle variant="fixed" />

        <div class="relative flex min-h-screen flex-col overflow-hidden">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -left-24 top-0 h-80 w-80 rounded-full bg-violet-400/20 blur-3xl dark:bg-violet-600/20"></div>
                <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-indigo-400/20 blur-3xl dark:bg-indigo-700/20"></div>
            </div>

            <header class="relative z-10 px-4 pb-2 pt-8 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-3xl justify-center">
                    <a href="{{ url('/') }}" class="inline-flex rounded-2xl outline-none ring-violet-500/25 transition hover:opacity-95 focus-visible:ring-2">
                        <x-psiconecta-logo variant="guest" />
                    </a>
                </div>
            </header>

            <main id="main-content" class="relative z-10 flex flex-1 items-center justify-center px-4 py-10 sm:px-6 lg:px-8" tabindex="-1">
                <div class="w-full max-w-lg text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-lg shadow-violet-500/30">
                        <x-ui.icon :name="$icon" class="h-8 w-8" />
                    </div>

                    <p class="mt-6 text-sm font-bold uppercase tracking-[0.2em] text-violet-600 dark:text-violet-400">
                        {{ __('Erro :code', ['code' => $code]) }}
                    </p>

                    <h1 class="mt-3 bg-gradient-to-r from-slate-900 via-violet-800 to-indigo-800 bg-clip-text text-3xl font-extrabold tracking-tight text-transparent dark:from-white dark:via-violet-200 dark:to-indigo-200 sm:text-4xl">
                        {{ $title }}
                    </h1>

                    <p class="mx-auto mt-4 max-w-md text-sm leading-relaxed text-slate-600 dark:text-slate-400 sm:text-base">
                        {{ $message }}
                    </p>

                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a
                            href="{{ url('/') }}"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 sm:w-auto"
                        >
                            <x-ui.icon name="home" class="h-4 w-4" />
                            {{ __('Voltar ao início') }}
                        </a>

                        @auth
                            <a
                                href="{{ route(auth()->user()->defaultAppRouteName()) }}"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 sm:w-auto"
                            >
                                {{ __('Ir para a minha área') }}
                            </a>
                        @else
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 sm:w-auto"
                            >
                                {{ __('Entrar') }}
                            </a>
                        @endauth
                    </div>
                </div>
            </main>

            <footer class="relative z-10 px-4 py-6 text-center text-xs text-slate-500 dark:text-slate-400">
                &copy; {{ date('Y') }} {{ config('app.name', 'PsicoVita') }}
            </footer>
        </div>
    </body>
</html>
