<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('partials.fonts')
        @include('layouts.partials.theme-init')
        @include('layouts.partials.ui-accent-init')
        @include('partials.head-vite')
    </head>
    <body class="psi-app-background font-sans text-gray-900 antialiased dark:text-gray-100">
        <x-skip-link />
        <x-theme-toggle variant="fixed" />

        <main id="main-content" class="flex min-h-screen w-full flex-col items-center pt-6 sm:justify-center sm:pt-0" tabindex="-1">
            <div>
                <a href="/" class="inline-flex rounded-2xl outline-none ring-violet-500/25 transition hover:opacity-95 focus-visible:ring-2">
                    <x-psiconecta-logo variant="guest" />
                </a>
            </div>

            <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md dark:bg-gray-900 sm:max-w-md sm:rounded-lg">
                {{ $slot }}
            </div>
        </main>
        <x-confirm-dialog />
    </body>
</html>
