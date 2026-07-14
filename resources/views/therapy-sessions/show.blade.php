@php
    $patient = $session->patient;
    $patientLabel = $patient?->name ?? ($session->session_mode === \App\Enums\SessionMode::WithObserver
        ? __('Escuta / supervisão')
        : __('Sessão sem utente principal'));
    $timeLabel = is_string($session->session_time)
        ? substr($session->session_time, 0, 5)
        : $session->session_time->format('H:i');

    $statusVariant = match ($session->status) {
        \App\Enums\TherapySessionStatus::Completed => 'success',
        \App\Enums\TherapySessionStatus::Scheduled => 'info',
        \App\Enums\TherapySessionStatus::Cancelled => 'danger',
    };

    $sessionMoment = $session->session_date->copy();
    try {
        [$hour, $minute] = array_map('intval', explode(':', $timeLabel));
        $sessionMoment->setTime($hour, $minute);
    } catch (\Throwable) {
        // mantém meia-noite
    }

    $isToday = $session->session_date->isToday();
    $isPast = $sessionMoment->isPast();
    $isUpcoming = $session->status === \App\Enums\TherapySessionStatus::Scheduled && ! $isPast;

    $heroSubtitle = match (true) {
        $session->status === \App\Enums\TherapySessionStatus::Cancelled => __('Sessão cancelada — detalhes mantidos para histórico.'),
        $isUpcoming && $isToday => __('Hoje às :time — sessão agendada.', ['time' => $timeLabel]),
        $isUpcoming => __('Agendada para :date às :time.', [
            'date' => $session->session_date->translatedFormat('d M Y'),
            'time' => $timeLabel,
        ]),
        $session->status === \App\Enums\TherapySessionStatus::Completed => __('Sessão concluída em :date.', [
            'date' => $session->session_date->translatedFormat('d M Y'),
        ]),
        default => __('Registo da sessão com :patient.', ['patient' => $patientLabel]),
    };

    $paymentBadge = null;
@endphp

<x-app-layout>
    <x-slot name="header">{{ $patientLabel }}</x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Sessão terapêutica')"
                :subtitle="$heroSubtitle"
                icon="clock"
            >
                <x-slot name="eyebrow">{{ $session->session_date->translatedFormat('l, d M Y') }} · {{ $timeLabel }}</x-slot>
                <x-slot name="actions">
                    @include('therapy-sessions.partials.video-conference-card', [
                        'session' => $session,
                        'canUseVideoConference' => $canUseVideoConference,
                        'variant' => 'button',
                    ])
                    <a
                        href="{{ route('therapy-sessions.index', ['month' => $session->session_date->format('Y-m')]) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Voltar à agenda') }}
                    </a>
                    <a
                        href="{{ route('therapy-sessions.edit', $session) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500"
                    >
                        <x-ui.icon name="calendar" class="h-4 w-4 shrink-0" />
                        {{ __('Editar sessão') }}
                    </a>
                </x-slot>
            </x-page-hero>

            {{-- Faixa de contexto --}}
            <section
                class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-950 p-6 shadow-xl shadow-indigo-950/20 ring-1 ring-white/10 sm:p-8 dark:border-slate-700/50"
                aria-label="{{ __('Resumo da sessão') }}"
            >
                <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-violet-500/20 blur-3xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-40 w-40 rounded-full bg-indigo-500/15 blur-3xl" aria-hidden="true"></div>

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <div
                            class="flex h-20 w-20 shrink-0 flex-col items-center justify-center rounded-2xl bg-white/10 text-center text-white ring-2 ring-white/15 backdrop-blur-sm sm:h-24 sm:w-24"
                            aria-hidden="true"
                        >
                            <span class="text-[10px] font-bold uppercase tracking-widest text-violet-200/90">{{ $session->session_date->translatedFormat('M') }}</span>
                            <span class="text-3xl font-extrabold leading-none sm:text-4xl">{{ $session->session_date->format('d') }}</span>
                            <span class="mt-0.5 text-xs font-semibold text-violet-100/80">{{ $timeLabel }}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge :variant="$statusVariant">{{ $session->status->label() }}</x-ui.badge>
                                <x-ui.badge variant="violet">{{ $session->type->label() }}</x-ui.badge>
                                @if ($isUpcoming)
                                    <x-ui.badge variant="warning">{{ $isToday ? __('Hoje') : __('Próxima') }}</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-3 text-lg font-bold text-white sm:text-xl">{{ $patientLabel }}</p>
                            <p class="mt-1 text-sm text-violet-100/80">
                                {{ $session->type === \App\Enums\TherapySessionType::Online ? __('Modalidade remota') : __('Atendimento presencial') }}
                                · {{ $sessionMoment->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2 lg:justify-end">
                        @include('therapy-sessions.partials.video-conference-card', [
                            'session' => $session,
                            'canUseVideoConference' => $canUseVideoConference,
                            'variant' => 'button',
                        ])
                        @if ($patient)
                            <a
                                href="{{ route('patients.show', $patient) }}"
                                class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20"
                            >
                                <x-ui.icon name="user" class="h-4 w-4 shrink-0" />
                                {{ __('Ficha do utente') }}
                            </a>
                        @endif
                        <a
                            href="{{ route('schedule.index', ['month' => $session->session_date->format('Y-m')]) }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20"
                        >
                            <x-ui.icon name="calendar" class="h-4 w-4 shrink-0" />
                            {{ __('Ver no calendário') }}
                        </a>
                    </div>
                </div>
            </section>

            <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
                {{-- Videoconferência (destaque no topo em mobile) --}}
                <div class="lg:col-span-12 lg:hidden">
                    @include('therapy-sessions.partials.video-conference-card', [
                        'session' => $session,
                        'canUseVideoConference' => $canUseVideoConference,
                    ])
                </div>

                {{-- Notas --}}
                <div class="space-y-6 lg:col-span-8">
                    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-4 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900/90">
                            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-600/10 text-violet-600 dark:bg-violet-400/15 dark:text-violet-300" aria-hidden="true">
                                    <x-ui.icon name="document-text" class="h-4 w-4" />
                                </span>
                                {{ __('Notas da sessão') }}
                            </h2>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Registo interno — visível apenas na área clínica.') }}</p>
                        </div>
                        <div class="px-5 py-6">
                            @if ($session->notes)
                                <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800 dark:text-slate-100">{{ $session->notes }}</p>
                            @else
                                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-5 py-10 text-center dark:border-slate-600 dark:bg-slate-800/50">
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('Sem notas registadas') }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Pode adicionar observações ao editar a sessão.') }}</p>
                                    <a
                                        href="{{ route('therapy-sessions.edit', $session) }}"
                                        class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400"
                                    >
                                        {{ __('Adicionar notas') }}
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- Detalhes em grelha --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Detalhes') }}</h2>
                        </div>
                        <dl class="grid gap-px bg-slate-100 sm:grid-cols-2 dark:bg-slate-700/80">
                            <div class="bg-white px-5 py-4 dark:bg-slate-900/80">
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Data') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $session->session_date->format('d/m/Y') }}</dd>
                            </div>
                            <div class="bg-white px-5 py-4 dark:bg-slate-900/80">
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Horário') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $timeLabel }}</dd>
                            </div>
                            <div class="bg-white px-5 py-4 dark:bg-slate-900/80">
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Estado') }}</dt>
                                <dd class="mt-1.5"><x-ui.badge :variant="$statusVariant">{{ $session->status->label() }}</x-ui.badge></dd>
                            </div>
                            <div class="bg-white px-5 py-4 dark:bg-slate-900/80">
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Modalidade') }}</dt>
                                <dd class="mt-1.5"><x-ui.badge variant="violet">{{ $session->type->label() }}</x-ui.badge></dd>
                            </div>
                            @if ($session->type === \App\Enums\TherapySessionType::Online && ($session->session_mode ?? \App\Enums\SessionMode::Individual) !== \App\Enums\SessionMode::Individual)
                                <div class="bg-white px-5 py-4 dark:bg-slate-900/80">
                                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Formato') }}</dt>
                                    <dd class="mt-1.5"><x-ui.badge variant="info">{{ ($session->session_mode ?? \App\Enums\SessionMode::Individual)->label() }}</x-ui.badge></dd>
                                </div>
                            @endif
                            <div class="bg-white px-5 py-4 sm:col-span-2 dark:bg-slate-900/80">
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Utente') }}</dt>
                                <dd class="mt-1">
                                    @if ($patient)
                                        <a href="{{ route('patients.show', $patient) }}" class="text-sm font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400">
                                            {{ $patient->name }}
                                        </a>
                                        @if ($patient->email)
                                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ $patient->email }}</span>
                                        @endif
                                    @else
                                        <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $patientLabel }}</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>

                {{-- Barra lateral --}}
                <aside class="space-y-6 lg:col-span-4">
                    {{-- Financeiro --}}
                    <section id="financeiro" class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50/90 to-teal-50/60 px-5 py-4 dark:border-slate-700 dark:from-emerald-950/40 dark:to-teal-950/30">
                            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-200">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600/10 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300" aria-hidden="true">
                                    <x-ui.icon name="currency" class="h-4 w-4" />
                                </span>
                                {{ __('Financeiro') }}
                            </h2>
                        </div>
                        @include('therapy-sessions.partials.financial-card', [
                            'session' => $session,
                            'billingOverview' => $billingOverview ?? [],
                            'observers' => $observers ?? collect(),
                            'familyGuests' => $familyGuests ?? collect(),
                        ])
                    </section>

                    <div class="hidden lg:block">
                        @include('therapy-sessions.partials.video-conference-card', [
                            'session' => $session,
                            'canUseVideoConference' => $canUseVideoConference,
                        ])
                    </div>

                    @if (($observers ?? collect())->isNotEmpty())
                        <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                            <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50/90 to-violet-50/60 px-5 py-4 dark:border-slate-700 dark:from-indigo-950/40 dark:to-violet-950/30">
                                <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-200">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600/10 text-indigo-700 dark:bg-indigo-400/15 dark:text-indigo-300" aria-hidden="true">
                                        <x-ui.icon name="users" class="h-4 w-4" />
                                    </span>
                                    {{ __('Observadores (escuta)') }} · {{ $observers->count() }}
                                </h2>
                            </div>
                            <ul class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach ($observers as $obs)
                                    <li class="space-y-3 p-5">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $obs->display_name }}</p>
                                            @if ($obs->user_id)
                                                <x-ui.badge variant="info" class="mt-1">{{ __('Profissional da clínica/equipa') }}</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="neutral" class="mt-1">{{ __('Externo') }}</x-ui.badge>
                                            @endif
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $obs->email }}</p>
                                        </div>
                                        @if ($obs->joinUrl())
                                            <p class="break-all rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $obs->joinUrl() }}</p>
                                        @endif
                                        <form method="post" action="{{ route('therapy-sessions.participants.invite', [$session, $obs]) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-800 transition hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-950/60">
                                                {{ __('Reenviar convite por e-mail') }}
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (($familyGuests ?? collect())->isNotEmpty())
                        <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                            <div class="border-b border-slate-100 bg-gradient-to-r from-teal-50/90 to-emerald-50/60 px-5 py-4 dark:border-slate-700 dark:from-teal-950/40 dark:to-emerald-950/30">
                                <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-teal-900 dark:text-teal-200">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-600/10 text-teal-700 dark:bg-teal-400/15 dark:text-teal-300" aria-hidden="true">
                                        <x-ui.icon name="users" class="h-4 w-4" />
                                    </span>
                                    {{ __('Convidados (casal/família)') }}
                                </h2>
                            </div>
                            <ul class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach ($familyGuests as $guest)
                                    <li class="space-y-3 p-5">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $guest->display_name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $guest->email }}</p>
                                            @if ($guest->patient_id)
                                                <x-ui.badge variant="success" class="mt-2">{{ __('Utente vinculado ao portal') }}</x-ui.badge>
                                            @endif
                                        </div>
                                        @if ($guest->joinUrl())
                                            <p class="break-all rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $guest->joinUrl() }}</p>
                                        @endif
                                        <form method="post" action="{{ route('therapy-sessions.participants.invite', [$session, $guest]) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-semibold text-teal-800 transition hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/40 dark:text-teal-200 dark:hover:bg-teal-950/60">
                                                {{ __('Reenviar convite') }}
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (($groupMembers ?? collect())->isNotEmpty())
                        <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                            <div class="border-b border-slate-100 bg-gradient-to-r from-violet-50/90 to-indigo-50/60 px-5 py-4 dark:border-slate-700 dark:from-violet-950/40 dark:to-indigo-950/30">
                                <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-violet-900 dark:text-violet-200">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-600/10 text-violet-700 dark:bg-violet-400/15 dark:text-violet-300" aria-hidden="true">
                                        <x-ui.icon name="users" class="h-4 w-4" />
                                    </span>
                                    {{ __('Grupo terapêutico') }} · {{ $groupMembers->count() }} {{ __('membros') }}
                                </h2>
                            </div>
                            <ul class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach ($groupMembers as $member)
                                    <li class="space-y-3 p-5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $member->display_name }}</p>
                                            @if ($member->joined_at)
                                                <x-ui.badge variant="success">{{ __('Presente') }}</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="neutral">{{ __('Aguardando') }}</x-ui.badge>
                                            @endif
                                        </div>
                                        @if ($member->email)
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $member->email }}</p>
                                        @endif
                                        @if ($member->joinUrl())
                                            <p class="break-all rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $member->joinUrl() }}</p>
                                        @endif
                                        @if ($member->email)
                                            <form method="post" action="{{ route('therapy-sessions.participants.invite', [$session, $member]) }}">
                                                @csrf
                                                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-800 transition hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-200 dark:hover:bg-violet-950/60">
                                                    {{ __('Reenviar convite') }}
                                                </button>
                                            </form>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    {{-- Ações --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Ações') }}</h2>
                        <ul class="mt-4 space-y-2" role="list">
                            <li>
                                <a
                                    href="{{ route('therapy-sessions.edit', $session) }}"
                                    class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:hover:border-violet-500 dark:hover:bg-violet-950/30"
                                >
                                    <x-ui.icon name="calendar" class="h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />
                                    {{ __('Alterar data ou estado') }}
                                </a>
                            </li>
                            <li>
                                <a
                                    href="{{ route('clinical-records.create') }}"
                                    class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:hover:border-violet-500 dark:hover:bg-violet-950/30"
                                >
                                    <x-ui.icon name="clipboard" class="h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />
                                    {{ __('Nova entrada no prontuário') }}
                                </a>
                            </li>
                            @if ($patient)
                                <li>
                                    <a
                                        href="{{ route('therapy-sessions.create', ['patient_id' => $patient->id, 'date' => $session->session_date->format('Y-m-d')]) }}"
                                        class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:hover:border-violet-500 dark:hover:bg-violet-950/30"
                                    >
                                        <x-ui.icon name="plus" class="h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />
                                        {{ __('Agendar nova sessão') }}
                                    </a>
                                </li>
                            @else
                                <li>
                                    <a
                                        href="{{ route('therapy-sessions.create', ['date' => $session->session_date->format('Y-m-d')]) }}"
                                        class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:hover:border-violet-500 dark:hover:bg-violet-950/30"
                                    >
                                        <x-ui.icon name="plus" class="h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />
                                        {{ __('Agendar nova sessão') }}
                                    </a>
                                </li>
                            @endif
                        </ul>

                        <div class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-700">
                            <x-confirm-form
                                method="post"
                                action="{{ route('therapy-sessions.destroy', $session) }}"
                                :title="__('Excluir sessão?')"
                                :message="__('A sessão será removida do histórico do utente. Esta ação não pode ser desfeita.')"
                                :confirm-label="__('Sim, excluir')"
                                variant="danger"
                                :validate="false"
                            >
                                @csrf
                                @method('delete')
                                <button
                                    type="submit"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-800 transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200 dark:hover:bg-rose-950/50"
                                >
                                    {{ __('Excluir sessão') }}
                                </button>
                            </x-confirm-form>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
