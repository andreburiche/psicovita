@extends('layouts.video-room')

@php
    $roomTitle = $session->patient?->name ?? match ($session->session_mode ?? \App\Enums\SessionMode::Individual) {
        \App\Enums\SessionMode::WithObserver => __('Escuta / supervisão'),
        \App\Enums\SessionMode::Family => __('Casal / família'),
        \App\Enums\SessionMode::Group => __('Grupo terapêutico'),
        default => __('Sessão por vídeo'),
    };
@endphp

@section('content')
    <div
        class="flex h-full min-h-screen flex-col"
        x-data="sessionVideoRoom({
            roomName: @js($videoCall->room_name),
            jitsiDomain: @js($jitsiDomain),
            displayName: @js($displayName),
            finishUrl: @js(route('therapy-sessions.video.finish', $session)),
            startUrl: @js(route('therapy-sessions.video.start', $session)),
            csrf: @js(csrf_token()),
            defaultApproach: @js($videoCall->approach ?? 'tcc'),
            allRecordingConsentsGiven: @js($allRecordingConsentsGiven),
            startAudioMuted: false,
            startVideoMuted: false,
        })"
        data-test="session-video-room"
    >
        <header class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-white/10 bg-slate-900/95 px-4 py-3 backdrop-blur-md sm:px-6">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-violet-300">{{ __('Sessão por vídeo') }}</p>
                @if ($isGroupSession ?? false)
                    <h1 class="truncate text-sm font-bold text-white sm:text-base">{{ __('Grupo terapêutico') }} · {{ ($groupMembers ?? collect())->count() }} {{ __('membros') }}</h1>
                    <p class="text-xs text-slate-400">{{ $session->session_date->format('d/m/Y') }} · {{ substr((string) $session->session_time, 0, 5) }}</p>
                @else
                    <h1 class="truncate text-sm font-bold text-white sm:text-base">{{ $roomTitle }}</h1>
                    <p class="text-xs text-slate-400">{{ $session->session_date->format('d/m/Y') }} · {{ substr((string) $session->session_time, 0, 5) }}</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                    :class="recording ? 'bg-rose-500/20 text-rose-200 ring-1 ring-rose-500/40' : 'bg-slate-800 text-slate-300'"
                >
                    <span class="h-2 w-2 rounded-full" :class="recording ? 'animate-pulse bg-rose-400' : 'bg-slate-500'"></span>
                    <span x-text="recording ? @js(__('Gravando')) : @js(__('Gravação pausada'))"></span>
                </span>
                <button
                    type="button"
                    @click="copyGuestLink()"
                    x-show="!@js($isGroupSession ?? false) && @js((bool) $session->patient_id)"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-white/15 bg-white/5 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/10"
                >
                    <x-ui.icon name="paper-clip" class="h-4 w-4" />
                    {{ __('Copiar link do paciente') }}
                </button>
                @if (($observerJoinUrl ?? null) && ($observers ?? collect())->count() === 1)
                    <button
                        type="button"
                        @click="copyObserverLink()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-400/30 bg-indigo-500/10 px-3 py-2 text-xs font-semibold text-indigo-200 transition hover:bg-indigo-500/20"
                    >
                        <x-ui.icon name="users" class="h-4 w-4" />
                        {{ __('Copiar link do observador') }}
                    </button>
                @endif
                <a
                    href="{{ route('therapy-sessions.show', $session) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-white/15 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/5"
                >
                    {{ __('Sair sem encerrar') }}
                </a>
            </div>
        </header>

        <div class="grid min-h-0 flex-1 gap-0 lg:grid-cols-12">
            <div class="relative min-h-[50vh] bg-black lg:col-span-9 lg:min-h-0">
                <div id="jitsi-container" class="absolute inset-0"></div>
            </div>

            <aside class="flex flex-col gap-4 overflow-y-auto border-t border-white/10 bg-slate-900 p-4 lg:col-span-3 lg:border-l lg:border-t-0">
                @if (($participants ?? collect())->isNotEmpty())
                    <div class="rounded-2xl border border-white/10 bg-slate-800/60 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Participantes') }}</p>
                        <ul class="mt-3 space-y-2">
                            @foreach ($participants as $participant)
                                <li class="flex items-center justify-between gap-2 text-xs">
                                    <span class="text-slate-200">
                                        <span class="font-semibold">{{ $participant->display_name }}</span>
                                        <span class="text-slate-500">· {{ $participant->role->label() }}</span>
                                    </span>
                                    @if ($participant->joined_at)
                                        <span class="shrink-0 rounded-full bg-sky-500/20 px-2 py-0.5 text-[10px] font-semibold text-sky-200">{{ __('Presente') }}</span>
                                    @elseif ($participant->hasRecordingConsent())
                                        <span class="shrink-0 rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold text-emerald-300">{{ __('Gravação OK') }}</span>
                                    @elseif ($participant->join_consent_at)
                                        <span class="shrink-0 rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-semibold text-amber-200">{{ __('Sem gravação') }}</span>
                                    @else
                                        <span class="shrink-0 rounded-full bg-slate-700 px-2 py-0.5 text-[10px] font-semibold text-slate-400">{{ __('Aguardando') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! ($allRecordingConsentsGiven ?? true) && ! empty($pendingRecordingConsents))
                    <div class="rounded-2xl border border-amber-500/30 bg-amber-950/30 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-300">{{ __('Consentimentos pendentes') }}</p>
                        <ul class="mt-2 list-inside list-disc text-xs text-amber-100">
                            @foreach ($pendingRecordingConsents as $pending)
                                <li>{{ $pending['name'] }} ({{ $pending['role'] }})</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (($observers ?? collect())->isNotEmpty())
                    <div class="rounded-2xl border border-indigo-500/30 bg-indigo-950/30 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-300">{{ __('Links dos observadores') }}</p>
                        <ul class="mt-3 space-y-2">
                            @foreach ($observers as $obs)
                                <li>
                                    <button
                                        type="button"
                                        @click="copyLink(@js($obs->joinUrl()), @js($obs->display_name))"
                                        class="flex w-full items-center justify-between gap-2 rounded-xl border border-white/10 bg-slate-800/60 px-3 py-2 text-left text-xs text-white transition hover:bg-slate-800"
                                    >
                                        <span class="truncate font-semibold">{{ $obs->display_name }}</span>
                                        <x-ui.icon name="paper-clip" class="h-4 w-4 shrink-0 text-indigo-300" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (($groupMembers ?? collect())->isNotEmpty())
                    <div class="rounded-2xl border border-violet-500/30 bg-violet-950/30 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-violet-300">{{ __('Links dos membros do grupo') }}</p>
                        <ul class="mt-3 space-y-2">
                            @foreach ($groupMembers as $member)
                                <li>
                                    <button
                                        type="button"
                                        @click="copyLink(@js($member->joinUrl()), @js($member->display_name))"
                                        class="flex w-full items-center justify-between gap-2 rounded-xl border border-white/10 bg-slate-800/60 px-3 py-2 text-left text-xs text-white transition hover:bg-slate-800"
                                    >
                                        <span class="truncate">
                                            <span class="font-semibold">{{ $member->display_name }}</span>
                                            @if ($member->joined_at)
                                                <span class="text-sky-300"> · {{ __('Presente') }}</span>
                                            @endif
                                        </span>
                                        <x-ui.icon name="paper-clip" class="h-4 w-4 shrink-0 text-violet-300" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (($familyGuests ?? collect())->isNotEmpty())
                    <div class="rounded-2xl border border-teal-500/30 bg-teal-950/30 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-teal-300">{{ __('Links dos convidados') }}</p>
                        <ul class="mt-3 space-y-2">
                            @foreach ($familyGuests as $guest)
                                <li>
                                    <button
                                        type="button"
                                        @click="copyLink(@js($guest->joinUrl()), @js($guest->display_name))"
                                        class="flex w-full items-center justify-between gap-2 rounded-xl border border-white/10 bg-slate-800/60 px-3 py-2 text-left text-xs text-white transition hover:bg-slate-800"
                                    >
                                        <span class="truncate font-semibold">{{ $guest->display_name }}</span>
                                        <x-ui.icon name="paper-clip" class="h-4 w-4 shrink-0 text-teal-300" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="rounded-2xl border border-violet-500/30 bg-violet-950/40 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-violet-300">{{ __('Assistente IA pós-sessão') }}</p>
                    <p class="mt-2 text-xs leading-relaxed text-slate-300">
                        {{ __('Ao encerrar, a gravação será transcrita e a IA gerará resumo clínico e devolutiva ao paciente conforme a abordagem escolhida.') }}
                    </p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-800/60 p-4">
                    <label for="approach" class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Abordagem terapêutica') }}</label>
                    <select id="approach" x-model="approach" class="mt-2 block w-full rounded-xl border border-slate-600 bg-slate-900 px-3 py-2.5 text-sm text-white">
                        @foreach ([
                            'tcc' => 'TCC',
                            'humanista' => __('Humanista'),
                            'freudiana' => __('Freudiana'),
                            'lacaniana' => __('Lacaniana'),
                            'jungiana' => __('Jungiana'),
                            'winnicottiana' => __('Winnicottiana'),
                            'sistemica' => __('Sistêmica'),
                        ] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-amber-500/30 bg-amber-950/30 p-4">
                    <input type="checkbox" x-model="consent" class="mt-0.5 rounded border-amber-600 text-violet-600 focus:ring-violet-500" />
                    <span class="text-xs leading-relaxed text-amber-100">
                        {{ __('Confirmo consentimento LGPD para gravar a sessão e processar áudio com IA (transcrição e devolutiva).') }}
                    </span>
                </label>

                <div class="mt-auto space-y-2">
                    <button
                        type="button"
                        @click="toggleRecording()"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/15 bg-slate-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                    >
                        <x-ui.icon name="video" class="h-4 w-4" />
                        <span x-text="recording ? @js(__('Pausar gravação')) : @js(__('Retomar gravação'))"></span>
                    </button>
                    <button
                        type="button"
                        @click="finishSession()"
                        :disabled="finishing || !consent || !allRecordingConsentsGiven"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:from-violet-500 hover:to-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <x-ui.icon name="check" class="h-4 w-4" />
                        <span x-text="finishing ? @js(__('Processando…')) : @js(__('Encerrar sessão e gerar devolutiva'))"></span>
                        <span class="sr-only">{{ __('Encerrar sessão e gerar devolutiva') }}</span>
                    </button>
                </div>

                <p x-show="error" x-text="error" class="text-xs text-rose-300" role="alert"></p>
                <p x-show="statusMessage" x-text="statusMessage" class="text-xs text-emerald-300"></p>
            </aside>
        </div>
    </div>
@endsection

@push('head')
    <script src="https://{{ $jitsiDomain }}/external_api.js" async></script>
@endpush

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('sessionVideoRoom', (config) => ({
                roomName: config.roomName,
                jitsiDomain: config.jitsiDomain,
                displayName: config.displayName,
                finishUrl: config.finishUrl,
                startUrl: config.startUrl,
                csrf: config.csrf,
                approach: config.defaultApproach,
                consent: false,
                recording: false,
                finishing: false,
                error: '',
                statusMessage: '',
                allRecordingConsentsGiven: config.allRecordingConsentsGiven,
                jitsiApi: null,
                mediaRecorder: null,
                recordedChunks: [],
                mixedStream: null,
                audioContext: null,

                init() {
                    this.notifyStart();
                    this.waitForJitsi().then(() => this.initJitsi());
                },

                async notifyStart() {
                    try {
                        await fetch(this.startUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrf,
                                'Accept': 'application/json',
                            },
                        });
                    } catch (e) {}
                },

                waitForJitsi() {
                    return new Promise((resolve) => {
                        if (window.JitsiMeetExternalAPI) {
                            resolve();
                            return;
                        }
                        const interval = setInterval(() => {
                            if (window.JitsiMeetExternalAPI) {
                                clearInterval(interval);
                                resolve();
                            }
                        }, 200);
                    });
                },

                initJitsi() {
                    this.jitsiApi = new JitsiMeetExternalAPI(this.jitsiDomain, {
                        roomName: this.roomName,
                        parentNode: document.getElementById('jitsi-container'),
                        userInfo: { displayName: this.displayName },
                        configOverwrite: {
                            startWithAudioMuted: config.startAudioMuted ?? false,
                            startWithVideoMuted: config.startVideoMuted ?? false,
                            prejoinPageEnabled: false,
                            disableDeepLinking: true,
                        },
                        interfaceConfigOverwrite: {
                            SHOW_JITSI_WATERMARK: false,
                            MOBILE_APP_PROMO: false,
                        },
                    });

                    this.jitsiApi.addListener('videoConferenceJoined', () => {
                        this.setupRecording();
                    });
                },

                async setupRecording() {
                    try {
                        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        const destination = this.audioContext.createMediaStreamDestination();

                        const localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
                        this.localStream = localStream;
                        this.audioContext.createMediaStreamSource(localStream).connect(destination);

                        this.jitsiApi.addListener('trackAdded', (track) => {
                            if (track?.type === 'audio' && track?.track) {
                                try {
                                    const remoteStream = new MediaStream([track.track]);
                                    this.audioContext.createMediaStreamSource(remoteStream).connect(destination);
                                } catch (e) {
                                    console.warn('Remote track mix failed', e);
                                }
                            }
                        });

                        const tracks = [
                            ...localStream.getVideoTracks(),
                            ...destination.stream.getAudioTracks(),
                        ];
                        this.mixedStream = new MediaStream(tracks);
                        this.startRecorder();
                    } catch (e) {
                        this.error = @js(__('Não foi possível iniciar a gravação. Verifique permissões de câmera e microfone.'));
                    }
                },

                startRecorder() {
                    if (!this.mixedStream) return;

                    this.recordedChunks = [];
                    const mimeType = this.pickMimeType();
                    const options = mimeType ? { mimeType } : undefined;
                    this.mediaRecorder = new MediaRecorder(this.mixedStream, options);
                    this.mediaRecorder.ondataavailable = (event) => {
                        if (event.data?.size > 0) {
                            this.recordedChunks.push(event.data);
                        }
                    };
                    this.mediaRecorder.start(5000);
                    this.recording = true;
                    this.statusMessage = @js(__('Gravação de vídeo e áudio iniciada automaticamente.'));
                },

                pickMimeType() {
                    const types = [
                        'video/webm;codecs=vp9,opus',
                        'video/webm;codecs=vp8,opus',
                        'video/webm',
                        'audio/webm;codecs=opus',
                        'audio/webm',
                        'audio/ogg;codecs=opus',
                        'audio/mp4',
                    ];
                    for (const type of types) {
                        if (MediaRecorder.isTypeSupported(type)) return type;
                    }
                    return '';
                },

                toggleRecording() {
                    if (!this.mediaRecorder) return;

                    if (this.recording) {
                        this.mediaRecorder.pause();
                        this.recording = false;
                    } else {
                        this.mediaRecorder.resume();
                        this.recording = true;
                    }
                },

                async finishSession() {
                    if (!this.consent) {
                        this.error = @js(__('Confirme o consentimento LGPD para encerrar com gravação.'));
                        return;
                    }

                    if (!this.allRecordingConsentsGiven) {
                        this.error = @js(__('Aguardando consentimento de gravação de todos os participantes que entraram na sala.'));
                        return;
                    }

                    this.finishing = true;
                    this.error = '';

                    try {
                        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                            await new Promise((resolve) => {
                                this.mediaRecorder.onstop = resolve;
                                this.mediaRecorder.stop();
                            });
                        }

                        if (this.jitsiApi) {
                            this.jitsiApi.dispose();
                        }

                        const blob = new Blob(this.recordedChunks, { type: this.mediaRecorder?.mimeType || 'video/webm' });
                        if (blob.size < 1024) {
                            throw new Error(@js(__('Gravação muito curta ou vazia. Permaneça na sala alguns segundos antes de encerrar.')));
                        }

                        const ext = (this.mediaRecorder?.mimeType || '').startsWith('video/') ? 'webm' : 'webm';
                        const form = new FormData();
                        form.append('recording', blob, 'sessao-' + Date.now() + '.' + ext);
                        form.append('approach', this.approach);
                        form.append('lgpd_recording_consent', '1');
                        form.append('_token', this.csrf);

                        const response = await fetch(this.finishUrl, {
                            method: 'POST',
                            body: form,
                            headers: { 'Accept': 'text/html,application/json' },
                            redirect: 'follow',
                        });

                        if (response.redirected) {
                            window.location.href = response.url;
                            return;
                        }

                        if (!response.ok) {
                            throw new Error(@js(__('Falha ao enviar a gravação. Tente novamente.')));
                        }

                        window.location.href = @js(route('therapy-sessions.video.review', $session));
                    } catch (e) {
                        this.error = e.message || String(e);
                        this.finishing = false;
                    }
                },

                copyGuestLink() {
                    navigator.clipboard.writeText(@js($guestJoinUrl)).then(() => {
                        this.statusMessage = @js(__('Link copiado — envie ao paciente.'));
                    });
                },

                copyObserverLink() {
                    navigator.clipboard.writeText(@js($observerJoinUrl ?? '')).then(() => {
                        this.statusMessage = @js(__('Link do observador copiado.'));
                    });
                },

                copyLink(url, name) {
                    navigator.clipboard.writeText(url).then(() => {
                        this.statusMessage = @js(__('Link copiado')) + ' — ' + name;
                    });
                },
            }));
        });
    </script>
@endpush
