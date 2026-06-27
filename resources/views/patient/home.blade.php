<x-patient-layout>
    <x-slot name="header">{{ __('Início') }}</x-slot>

    <x-patient-portal-shell>
    <x-patient-portal-hero
        :title="__('Olá, :name', ['name' => $user->name])"
        :subtitle="__('A sua área segura para mensagens, pagamentos de sessões e direitos de privacidade. Marcações clínicas são feitas pelo seu profissional.')"
        icon="user"
    />

    @if (($pendingPayments['count'] ?? 0) > 0)
        <div class="rounded-2xl border border-amber-300/80 bg-gradient-to-r from-amber-50 to-orange-50 p-5 shadow-sm dark:border-amber-700/50 dark:from-amber-950/50 dark:to-orange-950/40" role="status">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-800 dark:text-amber-300">{{ __('Ação necessária') }}</p>
                    <p class="mt-1 text-sm font-semibold text-amber-950 dark:text-amber-100">
                        {{ trans_choice(':count cobrança pendente|:count cobranças pendentes', $pendingPayments['count'], ['count' => $pendingPayments['count']]) }}
                        — <span class="text-lg font-extrabold tabular-nums">R$ {{ $pendingPayments['total_formatted'] }}</span>
                    </p>
                </div>
                <a
                    href="{{ route('patient.payments.index') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-amber-500"
                >
                    {{ __('Ver pagamentos') }}
                </a>
            </div>
        </div>
    @endif

    @if ($upcomingVideoSessions->isNotEmpty())
        <section class="overflow-hidden rounded-2xl border border-indigo-200/80 bg-white shadow-sm ring-1 ring-indigo-100 dark:border-indigo-900/50 dark:bg-slate-900/80 dark:ring-indigo-950" data-test="patient-upcoming-video">
            <div class="border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-violet-50/60 px-5 py-4 dark:border-indigo-900/40 dark:from-indigo-950/40 dark:to-violet-950/30">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-300">{{ __('Próximas consultas online') }}</p>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('Entre na videoconferência quando a sala estiver aberta.') }}</p>
                    </div>
                    <a href="{{ route('patient.sessions.index') }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-600 dark:text-indigo-300">{{ __('Ver todas') }} →</a>
                </div>
            </div>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($upcomingVideoSessions as $session)
                    @php
                        $canJoin = $portalSessions->canPatientJoinNow($session);
                        $timeLabel = is_string($session->session_time) ? substr($session->session_time, 0, 5) : $session->session_time->format('H:i');
                    @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $session->session_date->format('d/m/Y') }} · {{ $timeLabel }}</p>
                            <p class="text-xs text-slate-500">{{ $portalSessions->joinStatusLabel($session) }}</p>
                        </div>
                        @if ($canJoin)
                            <a href="{{ route('patient.sessions.join', $session) }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-500">{{ __('Entrar') }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($therapist)
        <section class="overflow-hidden rounded-2xl border border-sky-200/80 bg-white shadow-sm ring-1 ring-sky-100 dark:border-sky-800/50 dark:bg-slate-900/80 dark:ring-sky-900/30">
            <div class="border-b border-sky-100 bg-gradient-to-r from-sky-50 to-white px-5 py-4 dark:border-sky-900/40 dark:from-sky-950/40 dark:to-slate-900">
                <p class="text-xs font-bold uppercase tracking-wider text-sky-700 dark:text-sky-400">{{ __('Profissional de referência') }}</p>
            </div>
            <div class="flex items-center gap-4 p-5">
                <x-user-avatar :user="$therapist" size="md" class="ring-2 ring-sky-100 dark:ring-sky-900/50" />
                <div class="min-w-0">
                    <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $therapist->name }}</p>
                    @if ($therapist->email)
                        <a href="mailto:{{ $therapist->email }}" class="mt-0.5 block truncate text-sm text-sky-700 hover:underline dark:text-sky-400">{{ $therapist->email }}</a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section aria-label="{{ __('Atalhos') }}">
        <h2 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('O que deseja fazer?') }}</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <x-patient-quick-link
                :href="route('patient.sessions.index')"
                icon="video"
                tone="sky"
                :title="__('Consultas online')"
                :description="__('Entre na videoconferência com o seu profissional quando a sala estiver disponível.')"
            />
            <x-patient-quick-link
                :href="route('conversations.index')"
                icon="chat-bubble-left-right"
                tone="violet"
                :title="__('Conversas')"
                :description="__('Comunicação interna com o seu profissional — consulte aqui quando iniciar sessão.')"
            />
            <x-patient-quick-link
                :href="route('patient.payments.index')"
                icon="currency"
                tone="amber"
                :title="__('Pagamentos')"
                :description="__('Consulte cobranças de sessões e pague com PIX ou cartão de forma segura.')"
            />
            <x-patient-quick-link
                :href="route('patient.lgpd.index')"
                icon="shield-check"
                tone="emerald"
                :title="__('Privacidade (LGPD)')"
                :description="__('Exporte dados, solicite correções ou exercite os seus direitos como titular.')"
            />
            <x-patient-quick-link
                :href="route('profile.edit')"
                icon="user"
                tone="slate"
                :title="__('Configurações da conta')"
                :description="__('Nome, e-mail, palavra-passe, foto e preferências de aparência.')"
            />
        </div>
    </section>

    <section id="notificacoes" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <x-ui.section-heading icon="bell" icon-tone="emerald" :title="__('Notificações')" :subtitle="__('Alertas de mensagens, pagamentos e convites.')" class="flex-1" />
            @if ($notifications->whereNull('read_at')->isNotEmpty())
                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-emerald-700 hover:text-emerald-600 dark:text-emerald-300">
                        {{ __('Marcar todas como lidas') }}
                    </button>
                </form>
            @endif
        </div>
        <x-notifications-feed :notifications="$notifications" />
    </section>
    </x-patient-portal-shell>
</x-patient-layout>
