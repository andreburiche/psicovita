<x-patient-layout>
    <x-slot name="header">{{ __('Consultas online') }}</x-slot>

    <x-patient-portal-shell>
        <x-patient-portal-hero
            :title="__('Consultas por vídeo')"
            :subtitle="__('Entre na sala quando o profissional abrir a chamada. Use um navegador com câmera e microfone.')"
            icon="video"
        />

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($sessions->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900/80">
                <x-ui.icon name="video" class="mx-auto h-10 w-10 text-slate-400" />
                <p class="mt-4 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Nenhuma consulta online próxima') }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Quando o profissional agendar uma sessão remota, ela aparecerá aqui.') }}</p>
            </div>
        @else
            <div class="space-y-3" data-test="patient-video-sessions">
                @foreach ($sessions as $session)
                    @php
                        $canJoin = $portalSessions->canPatientJoinNow($session);
                        $timeLabel = is_string($session->session_time)
                            ? substr($session->session_time, 0, 5)
                            : $session->session_time->format('H:i');
                        $isLive = $session->videoCall?->status === \App\Enums\VideoCallStatus::Live;
                    @endphp
                    <article @class([
                        'overflow-hidden rounded-2xl border bg-white shadow-sm dark:bg-slate-900/80',
                        'border-indigo-300 ring-2 ring-indigo-500/20 dark:border-indigo-700' => $isLive,
                        'border-slate-200/90 dark:border-slate-700' => ! $isLive,
                    ])>
                        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ $session->session_date->translatedFormat('d M Y') }} · {{ $timeLabel }}
                                    </p>
                                    @if ($isLive)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold uppercase text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200">
                                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-indigo-500"></span>
                                            {{ __('Ao vivo') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $session->professional?->name ?? __('Profissional') }}
                                    · {{ $session->status->label() }}
                                </p>
                                <p class="mt-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                                    {{ $portalSessions->joinStatusLabel($session) }}
                                </p>
                            </div>

                            @if ($canJoin)
                                <a
                                    href="{{ route('patient.sessions.join', $session) }}"
                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-bold text-white shadow-md transition hover:from-indigo-500 hover:to-violet-500"
                                >
                                    <x-ui.icon name="video" class="h-4 w-4" />
                                    {{ __('Entrar na sala') }}
                                </a>
                            @else
                                <span class="inline-flex shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    {{ __('Indisponível') }}
                                </span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </x-patient-portal-shell>
</x-patient-layout>
