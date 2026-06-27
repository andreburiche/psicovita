@php
    $user = Auth::user();
    $patientPortal = $user?->usesPatientPortalExperience() ?? false;
    $professionalArea = $user?->isProfessional() && ! $patientPortal;
    $adminArea = $user?->isAdmin() ?? false;
    $supportDeskArea = $user?->isSupportAgent() ?? false;
    $sidebarTagline = match (true) {
        $patientPortal => __('Área do paciente'),
        $adminArea => __('Administração'),
        $supportDeskArea => __('Atendimento'),
        $professionalArea => __('Área clínica'),
        $user?->canManageLgpdRequests() => __('Conformidade'),
        default => config('app.name'),
    };
@endphp

<div class="relative flex h-full flex-col overflow-hidden border-r border-violet-500/25 bg-slate-950 shadow-[4px_0_24px_-4px_rgba(91,33,182,0.35)] dark:border-violet-400/20 dark:shadow-[4px_0_24px_-4px_rgba(0,0,0,0.45)]">
    <div class="pointer-events-none absolute -left-24 top-0 h-64 w-64 rounded-full bg-violet-500/35 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute bottom-0 right-0 h-48 w-48 rounded-full bg-indigo-600/25 blur-3xl" aria-hidden="true"></div>

    <div class="relative z-10 flex h-full flex-col">
        <div class="flex h-16 shrink-0 items-center gap-2 border-b border-white/10 bg-black/20 px-3 backdrop-blur-sm sm:px-4">
            <a
                href="{{ route($user?->defaultAppRouteName() ?? 'login') }}"
                class="flex min-w-0 flex-1 items-center overflow-hidden rounded-lg py-1 outline-none ring-white/25 transition hover:bg-white/5 focus-visible:ring-2"
                aria-label="{{ config('app.logo_alt', config('app.name')) }}"
                x-bind:title="sidebarCollapsed && isDesktop ? @js(__('Ir para o início')) : ''"
            >
                <x-psiconecta-logo
                    variant="sidebar"
                    :collapsible="true"
                    :show-tagline="true"
                    :tagline-label="$sidebarTagline"
                    class="min-w-0"
                />
            </a>
            <button
                type="button"
                class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-violet-200 shadow-sm transition hover:border-white/25 hover:bg-white/10 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-400 lg:inline-flex"
                @click="toggleSidebarCollapse()"
                :aria-expanded="! sidebarCollapsed"
                :aria-label="sidebarCollapsed ? @js(__('Expandir menu lateral')) : @js(__('Recolher menu lateral'))"
            >
                <span class="inline-flex transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''">
                    <x-ui.icon name="chevrons-left" class="h-5 w-5" />
                </span>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="{{ __('Menu principal') }}">
            @if ($supportDeskArea)
                <x-ui.nav-group-label :label="__('Atendimento')" />

                <x-sidebar-link
                    :href="route('admin.support.index')"
                    :active="request()->routeIs('admin.support.*')"
                    :label="__('Central de suporte')"
                    :badge="($supportPendingCount ?? 0) > 0 ? (string) $supportPendingCount : null"
                >
                    <x-slot name="icon"><x-ui.icon name="messages" /></x-slot>
                </x-sidebar-link>
            @endif

            @if ($patientPortal)
                <x-sidebar-link :href="route('patient.home')" :active="request()->routeIs('patient.home')" :label="__('Início')">
                    <x-slot name="icon"><x-ui.icon name="home" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('conversations.index')" :active="request()->routeIs('conversations.*') || request()->routeIs('messages.*')" :label="__('Conversas')">
                    <x-slot name="icon"><x-ui.icon name="message-square" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('patient.payments.index')" :active="request()->routeIs('patient.payments.*')" :label="__('Pagamentos')">
                    <x-slot name="icon"><x-ui.icon name="wallet" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('patient.lgpd.index')" :active="request()->routeIs('patient.lgpd.*')" :label="__('Privacidade')">
                    <x-slot name="icon"><x-ui.icon name="shield" /></x-slot>
                </x-sidebar-link>
            @endif

            @if ($professionalArea)
                <x-ui.nav-group-label :label="__('Clínico')" />

                <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" :label="__('Dashboard')">
                    <x-slot name="icon"><x-ui.icon name="dashboard" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('schedule.index')" :active="request()->routeIs('schedule.index')" :label="__('Agenda')">
                    <x-slot name="icon"><x-ui.icon name="calendar" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link
                    :href="route('patients.index')"
                    :active="request()->routeIs('patients.*')"
                    :label="__('Pacientes')"
                    :badge="($patientQuota['limited'] ?? false) ? ($patientQuota['count'].'/'.$patientQuota['limit']) : null"
                >
                    <x-slot name="icon"><x-ui.icon name="user-round" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('therapy-sessions.index')" :active="request()->routeIs('therapy-sessions.*')" :label="__('Sessões')">
                    <x-slot name="icon"><x-ui.icon name="brain" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('anamnesis-forms.index')" :active="request()->routeIs('anamnesis-forms.*')" :label="__('Anamnese')">
                    <x-slot name="icon"><x-ui.icon name="clipboard-list" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('clinical-records.index')" :active="request()->routeIs('clinical-records.*')" :label="__('Prontuário')">
                    <x-slot name="icon"><x-ui.icon name="file-text" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('conversations.index')" :active="request()->routeIs('conversations.*') || request()->routeIs('messages.*')" :label="__('Conversas')">
                    <x-slot name="icon"><x-ui.icon name="message-square" /></x-slot>
                </x-sidebar-link>

                <x-ui.nav-group-label :label="__('Gestão')" />

                <x-sidebar-link :href="route('payments.index')" :active="request()->routeIs('payments.*')" :label="__('Financeiro')">
                    <x-slot name="icon"><x-ui.icon name="wallet" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('subscription.checkout')" :active="request()->routeIs('subscription.*')" :label="__('Assinatura')">
                    <x-slot name="icon"><x-ui.icon name="currency" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" :label="__('Relatórios')">
                    <x-slot name="icon"><x-ui.icon name="bar-chart-3" /></x-slot>
                </x-sidebar-link>

                @if ($user?->canUseSubscriptionFeature('use_ai'))
                    <x-sidebar-link :href="route('ai.index')" :active="request()->routeIs('ai.*')" :label="__('IA Assistente')">
                        <x-slot name="icon"><x-ui.icon name="sparkles" /></x-slot>
                    </x-sidebar-link>
                @endif

                <x-sidebar-link :href="route('schedule-blocks.index')" :active="request()->routeIs('schedule-blocks.*')" :label="__('Bloqueios')">
                    <x-slot name="icon"><x-ui.icon name="ban" /></x-slot>
                </x-sidebar-link>
            @endif

            @if ($user?->canManageLgpdRequests())
                <x-ui.nav-group-label :label="__('LGPD')" />

                @if ($adminArea)
                    <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" :label="__('Painel')">
                        <x-slot name="icon"><x-ui.icon name="dashboard" /></x-slot>
                    </x-sidebar-link>
                @endif

                <x-sidebar-link :href="route('admin.lgpd.metrics')" :active="request()->routeIs('admin.lgpd.metrics')" :label="__('Métricas LGPD')">
                    <x-slot name="icon"><x-ui.icon name="chart-line" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('admin.lgpd.audit')" :active="request()->routeIs('admin.lgpd.audit*')" :label="__('Auditoria')">
                    <x-slot name="icon"><x-ui.icon name="magnifying-glass" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('admin.lgpd.accessibility')" :active="request()->routeIs('admin.lgpd.accessibility')" :label="__('Acessibilidade')">
                    <x-slot name="icon"><x-ui.icon name="eye" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('admin.lgpd.requests.index')" :active="request()->routeIs('admin.lgpd.requests.*')" :label="__('Solicitações LGPD')">
                    <x-slot name="icon"><x-ui.icon name="shield-alert" /></x-slot>
                </x-sidebar-link>
            @endif

            @if ($adminArea)
                <x-ui.nav-group-label :label="__('Site')" />

                <x-sidebar-link :href="route('admin.site.settings')" :active="request()->routeIs('admin.site.settings*')" :label="__('Redes e WhatsApp')">
                    <x-slot name="icon"><x-ui.icon name="globe" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('admin.subscriptions.index')" :active="request()->routeIs('admin.subscriptions.*')" :label="__('Assinaturas profissionais')">
                    <x-slot name="icon"><x-ui.icon name="banknote" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('admin.site.plans')" :active="request()->routeIs('admin.site.plans*')" :label="__('Planos e preços')">
                    <x-slot name="icon"><x-ui.icon name="currency" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('admin.site.partners')" :active="request()->routeIs('admin.site.partners*')" :label="__('Parceiros')">
                    <x-slot name="icon"><x-ui.icon name="briefcase" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('admin.integrations.whatsapp')" :active="request()->routeIs('admin.integrations.whatsapp*')" :label="__('WhatsApp API')">
                    <x-slot name="icon"><x-ui.icon name="message-square" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link
                    :href="route('admin.support.index')"
                    :active="request()->routeIs('admin.support.*') && ! request()->routeIs('admin.support.metrics')"
                    :label="__('Central de suporte')"
                    :badge="($supportPendingCount ?? 0) > 0 ? (string) $supportPendingCount : null"
                >
                    <x-slot name="icon"><x-ui.icon name="messages" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('admin.support.metrics')" :active="request()->routeIs('admin.support.metrics')" :label="__('Métricas chatbot')">
                    <x-slot name="icon"><x-ui.icon name="bar-chart-3" /></x-slot>
                </x-sidebar-link>

                <x-sidebar-link :href="route('admin.chatbot.intents.index')" :active="request()->routeIs('admin.chatbot.intents.*')" :label="__('Intents chatbot')">
                    <x-slot name="icon"><x-ui.icon name="sparkles" /></x-slot>
                </x-sidebar-link>
            @endif
        </nav>

        <div class="shrink-0 border-t border-white/10 bg-black/25 p-3 backdrop-blur-sm">
            <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" :label="__('Configurações')">
                <x-slot name="icon"><x-ui.icon name="settings" /></x-slot>
            </x-sidebar-link>
        </div>
    </div>
</div>
