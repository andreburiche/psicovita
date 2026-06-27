<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if (Auth::user()?->isProfessional())
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                        <x-nav-link :href="route('patients.index')" :active="request()->routeIs('patients.*')">
                            {{ __('Pacientes') }}
                        </x-nav-link>
                        <x-nav-link :href="route('therapy-sessions.index')" :active="request()->routeIs('therapy-sessions.*')">
                            {{ __('Sessões') }}
                        </x-nav-link>
                        <x-nav-link :href="route('schedule.index')" :active="request()->routeIs('schedule.index')">
                            {{ __('Agenda') }}
                        </x-nav-link>
                        <x-nav-link :href="route('schedule-blocks.index')" :active="request()->routeIs('schedule-blocks.*')">
                            {{ __('Bloqueios') }}
                        </x-nav-link>
                        <x-nav-link :href="route('payments.index')" :active="request()->routeIs('payments.*')">
                            {{ __('Financeiro') }}
                        </x-nav-link>
                        <x-nav-link :href="route('clinical-records.index')" :active="request()->routeIs('clinical-records.*')">
                            {{ __('Prontuário') }}
                        </x-nav-link>
                        <x-nav-link :href="route('conversations.index')" :active="request()->routeIs('conversations.*') || request()->routeIs('messages.*')">
                            {{ __('Conversas') }}
                        </x-nav-link>
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            {{ __('Relatórios') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <x-ui.icon name="chevron-down-mini" class="h-4 w-4 fill-current" />
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <x-logout-button variant="dropdown">{{ __('Log Out') }}</x-logout-button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <x-ui.icon name="menu" class="h-6 w-6 inline-flex" x-bind:class="{ 'hidden': open, 'inline-flex': ! open }" />
                    <x-ui.icon name="x" class="hidden h-6 w-6" x-bind:class="{ 'hidden': ! open, 'inline-flex': open }" />
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if (Auth::user()?->isProfessional())
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('patients.index')" :active="request()->routeIs('patients.*')">
                    {{ __('Pacientes') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('therapy-sessions.index')" :active="request()->routeIs('therapy-sessions.*')">
                    {{ __('Sessões') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('schedule.index')" :active="request()->routeIs('schedule.index')">
                    {{ __('Agenda') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('schedule-blocks.index')" :active="request()->routeIs('schedule-blocks.*')">
                    {{ __('Bloqueios') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('payments.index')" :active="request()->routeIs('payments.*')">
                    {{ __('Financeiro') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('clinical-records.index')" :active="request()->routeIs('clinical-records.*')">
                    {{ __('Prontuário') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('conversations.index')" :active="request()->routeIs('conversations.*') || request()->routeIs('messages.*')">
                    {{ __('Conversas') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    {{ __('Relatórios') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <x-logout-button variant="responsive">{{ __('Log Out') }}</x-logout-button>
            </div>
        </div>
    </div>
</nav>
