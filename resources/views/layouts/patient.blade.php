<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@isset($header){{ trim(strip_tags((string) $header)) }} — @endisset{{ config('app.name', 'Laravel') }}</title>

        @include('partials.fonts')
        @include('layouts.partials.theme-init')
        @include('layouts.partials.ui-accent-init')
        @include('partials.head-vite')
    </head>
    <body class="psi-app-background h-full font-sans text-slate-800 antialiased transition-colors duration-300 dark:text-slate-100">
        <x-skip-link />
        <div class="flex min-h-full flex-col">
            @include('layouts.partials.patient-header')

            <main id="main-content" class="relative flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-8" tabindex="-1">
                <div class="mx-auto max-w-5xl space-y-6">
                    <x-flash-alert />
                    @if (session('warning'))
                        <x-flash-alert type="warning" :message="session('warning')" />
                    @endif
                    @if (session('error'))
                        <x-flash-alert type="error" :message="session('error')" />
                    @endif
                    @if ($errors->any())
                        <x-flash-alert type="error" :message="$errors->first()" />
                    @endif

                    {{ $slot }}
                </div>
            </main>

            <footer class="mt-auto border-t border-emerald-200/50 bg-white/60 py-5 text-center dark:border-emerald-900/30 dark:bg-slate-900/40">
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Precisa de ajuda? Fale com o seu profissional.') }}</p>
            </footer>
        </div>

        <x-confirm-dialog />
        <x-inactivity-guard />
        <x-chatbot-widget />
        @stack('scripts')
    </body>
</html>
