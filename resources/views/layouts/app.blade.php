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
    <body
        class="psi-app-background h-full font-sans text-slate-900 antialiased transition-colors duration-300 dark:text-slate-100"
        x-data="appShell"
        @keydown.tab="trapSidebarTab($event)"
    >
        <x-skip-link />

        <div
            x-show="sidebarOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm dark:bg-black/60 lg:hidden"
            @click="closeSidebar()"
            aria-hidden="true"
        ></div>

        <aside
            id="app-sidebar"
            class="fixed inset-y-0 left-0 z-50 w-64 max-w-[85vw] -translate-x-full transform border-r border-white/10 shadow-2xl transition-[transform,width] duration-300 ease-out lg:max-w-none lg:translate-x-0"
            :class="{
                'translate-x-0': sidebarOpen,
                'lg:w-[4.25rem]': sidebarCollapsed && isDesktop,
                'lg:w-64': ! sidebarCollapsed || ! isDesktop,
            }"
            :aria-modal="sidebarOpen && ! isDesktop ? 'true' : null"
            :role="sidebarOpen && ! isDesktop ? 'dialog' : null"
            aria-label="{{ __('Navegação lateral') }}"
        >
            @include('layouts.partials.app-sidebar')
        </aside>

        <div
            class="flex min-h-full flex-col transition-[padding] duration-300 ease-out"
            :class="sidebarCollapsed && isDesktop ? 'lg:pl-[4.25rem]' : 'lg:pl-64'"
        >
            @include('layouts.partials.app-topbar')

            <main id="main-content" class="relative flex-1 px-4 py-6 sm:px-6 lg:px-8" tabindex="-1">
                <x-flash-alert />
                @if (session('warning'))
                    <x-flash-alert type="warning" :message="session('warning')" class="mb-6" />
                @endif
                @if (session('error'))
                    <x-flash-alert type="error" :message="session('error')" class="mb-6" />
                @endif
                @if (session('subscription_blocked'))
                    <x-flash-alert type="error" :message="session('subscription_blocked')" :dismissible="true" class="mb-6" />
                @endif
                @if ($errors->any())
                    <x-flash-alert type="error" :message="$errors->first()" class="mb-6" />
                @endif

                {{ $slot }}
            </main>
        </div>

        <x-confirm-dialog />

        <x-chatbot-widget />

        <script>
            window.__PSICONECTA_I18N = {
                required: @json(__('Campo obrigatório.')),
                cpf: @json(__('CPF inválido.')),
                phone: @json(__('Telefone inválido.')),
                cep: @json(__('CEP inválido.')),
                date: @json(__('Data inválida (use dd/mm/aaaa).')),
                email: @json(__('E-mail inválido.')),
            };
        </script>
        @stack('scripts')
    </body>
</html>
