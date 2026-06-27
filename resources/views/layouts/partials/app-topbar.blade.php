@php
    $user = Auth::user();
    $clinicalTopbar = $user?->isProfessional() && ! $user?->usesPatientPortalExperience();
@endphp

<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-4 border-b border-violet-200/50 bg-white/95 px-4 shadow-md shadow-violet-900/5 backdrop-blur-md dark:border-slate-700/80 dark:bg-slate-900/95 dark:shadow-black/20 sm:px-6 lg:px-8">
    <button
        type="button"
        id="sidebar-toggle"
        class="inline-flex items-center justify-center rounded-xl border border-slate-300/80 bg-white p-2 text-slate-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-50/80 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-violet-500 dark:hover:bg-slate-700 lg:hidden"
        @click="openSidebar()"
        :aria-expanded="sidebarOpen ? 'true' : 'false'"
        aria-controls="app-sidebar"
        aria-label="{{ __('Abrir menu') }}"
    >
        <x-ui.icon name="menu" class="h-5 w-5" />
    </button>

    <div class="min-w-0 max-w-[40%] flex-1 sm:max-w-xs lg:max-w-sm">
        @isset($header)
            <h1 class="truncate text-base font-extrabold tracking-tight text-slate-800 dark:text-slate-100 sm:text-lg">
                {{ is_string($header) ? strip_tags($header) : $header }}
            </h1>
        @else
            <h1 class="truncate text-base font-extrabold tracking-tight text-slate-800 dark:text-slate-100 sm:text-lg">{{ config('app.name') }}</h1>
        @endisset
    </div>

    @if ($clinicalTopbar)
        <form action="{{ route('patients.index') }}" method="GET" class="hidden max-w-md flex-1 md:block" role="search">
            <label for="global-search" class="sr-only">{{ __('Buscar pacientes') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-violet-500 dark:text-violet-400">
                    <x-ui.icon name="search" class="h-5 w-5" />
                </span>
                <input id="global-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Buscar pacientes…') }}" class="w-full rounded-xl border border-slate-300/90 bg-white py-2.5 pl-10 pr-4 text-sm font-medium text-slate-900 shadow-inner shadow-slate-200/50 placeholder:text-slate-500 transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400 dark:shadow-none dark:focus:border-violet-500" />
            </div>
        </form>
    @else
        <div class="hidden min-w-0 flex-1 md:block" aria-hidden="true"></div>
    @endif

    <div class="flex items-center gap-2 sm:gap-3">
        @if ($clinicalTopbar)
            <a href="{{ route('therapy-sessions.create') }}" class="hidden items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-violet-600/35 transition hover:from-violet-500 hover:to-indigo-500 sm:inline-flex">
                <x-ui.icon name="plus" class="h-5 w-5" />
                {{ __('Nova sessão') }}
            </a>
            <a href="{{ route('therapy-sessions.create') }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 p-2.5 text-white shadow-lg shadow-violet-600/35 sm:hidden" title="{{ __('Nova sessão') }}" aria-label="{{ __('Nova sessão') }}">
                <x-ui.icon name="plus" class="h-5 w-5" />
            </a>
        @elseif ($user?->usesPatientPortalExperience())
            <a href="{{ route('patient.payments.index') }}" class="hidden items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/30 transition hover:from-emerald-500 hover:to-teal-500 sm:inline-flex">
                <x-ui.icon name="wallet" class="h-5 w-5" />
                {{ __('Pagamentos') }}
            </a>
            <a href="{{ route('patient.payments.index') }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 p-2.5 text-white shadow-lg shadow-emerald-600/30 sm:hidden" title="{{ __('Pagamentos') }}" aria-label="{{ __('Pagamentos') }}">
                <x-ui.icon name="wallet" class="h-5 w-5" />
            </a>
        @endif

        @auth
            <x-theme-toggle />

            <x-notifications-bell variant="clinical" />

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-2 rounded-xl border border-slate-300/90 bg-white py-1.5 pl-1.5 pr-2 text-left text-sm font-semibold text-slate-800 shadow-sm transition hover:border-violet-300 hover:bg-violet-50/50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-violet-500 dark:hover:bg-slate-700">
                        <x-user-avatar :user="Auth::user()" size="sm" />
                        <span class="hidden max-w-[10rem] truncate sm:inline">{{ Auth::user()->name }}</span>
                        <x-ui.icon name="chevron-down-mini" class="hidden h-4 w-4 text-violet-500 dark:text-violet-400 sm:block" />
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">{{ __('Perfil') }}</x-dropdown-link>
                    @if (Auth::user()->usesPatientPortalExperience())
                        <x-dropdown-link :href="route('patient.payments.index')">{{ __('Pagamentos') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('patient.lgpd.index')">{{ __('Privacidade') }}</x-dropdown-link>
                    @elseif (Auth::user()->isProfessional())
                        <x-dropdown-link :href="route('subscription.checkout')">{{ __('Assinatura') }}</x-dropdown-link>
                    @elseif (Auth::user()->isAdmin())
                        <x-dropdown-link :href="route('admin.lgpd.requests.index')">{{ __('Solicitações LGPD') }}</x-dropdown-link>
                    @endif
                    <x-logout-button variant="dropdown" />
                </x-slot>
            </x-dropdown>
        @endauth
    </div>
</header>
