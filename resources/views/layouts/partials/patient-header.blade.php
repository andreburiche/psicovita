<header class="sticky top-0 z-30 border-b border-emerald-200/70 bg-white/95 shadow-sm shadow-emerald-900/5 backdrop-blur-md dark:border-emerald-800/40 dark:bg-slate-950/95">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-3 py-3">
            <a href="{{ route('patient.home') }}" class="flex min-w-0 shrink-0 items-center gap-2 rounded-xl outline-none ring-emerald-500/40 transition hover:opacity-90 focus-visible:ring-2">
                <x-psiconecta-logo variant="patient" class="min-w-0" />
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <x-theme-toggle />
                <x-notifications-bell variant="patient" />
                <div class="hidden h-8 w-px bg-slate-200 dark:bg-slate-700 sm:block" aria-hidden="true"></div>
                <div class="hidden min-w-0 max-w-[10rem] items-center gap-2 sm:flex">
                    <x-user-avatar :user="Auth::user()" size="xs" />
                    <span class="truncate text-sm font-medium text-slate-700 dark:text-slate-200" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</span>
                </div>
                <x-logout-button variant="inline" />
            </div>
        </div>

        <nav
            class="-mx-1 mb-2 flex gap-1 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            aria-label="{{ __('Navegação do paciente') }}"
        >
            @php
                $navItem = fn (bool $active) => [
                    'inline-flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-emerald-500/30',
                    'bg-emerald-600 text-white shadow-md shadow-emerald-600/25' => $active,
                    'text-slate-600 hover:bg-emerald-50 hover:text-emerald-800 dark:text-slate-400 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300' => ! $active,
                ];
            @endphp
            <a href="{{ route('patient.home') }}" @class($navItem(request()->routeIs('patient.home'))) @if (request()->routeIs('patient.home')) aria-current="page" @endif>
                <x-ui.icon name="dashboard" class="h-4 w-4 shrink-0 opacity-80" />
                {{ __('Início') }}
            </a>
            <a href="{{ route('patient.sessions.index') }}" @class($navItem(request()->routeIs('patient.sessions.*'))) @if (request()->routeIs('patient.sessions.*')) aria-current="page" @endif>
                <x-ui.icon name="video" class="h-4 w-4 shrink-0 opacity-80" />
                {{ __('Consultas online') }}
            </a>
            <a href="{{ route('conversations.index') }}" @class($navItem(request()->routeIs('conversations.*') || request()->routeIs('messages.*'))) @if (request()->routeIs('conversations.*') || request()->routeIs('messages.*')) aria-current="page" @endif>
                <x-ui.icon name="chat-bubble-left-right" class="h-4 w-4 shrink-0 opacity-80" />
                {{ __('Conversas') }}
            </a>
            <a href="{{ route('patient.payments.index') }}" @class($navItem(request()->routeIs('patient.payments.*'))) @if (request()->routeIs('patient.payments.*')) aria-current="page" @endif>
                <x-ui.icon name="currency" class="h-4 w-4 shrink-0 opacity-80" />
                {{ __('Pagamentos') }}
            </a>
            <a href="{{ route('patient.lgpd.index') }}" @class($navItem(request()->routeIs('patient.lgpd.*'))) @if (request()->routeIs('patient.lgpd.*')) aria-current="page" @endif>
                <x-ui.icon name="shield-check" class="h-4 w-4 shrink-0 opacity-80" />
                {{ __('Privacidade') }}
            </a>
            <a href="{{ route('profile.edit') }}" @class($navItem(request()->routeIs('profile.*'))) @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                <x-ui.icon name="user" class="h-4 w-4 shrink-0 opacity-80" />
                {{ __('Conta') }}
            </a>
        </nav>
    </div>
</header>
