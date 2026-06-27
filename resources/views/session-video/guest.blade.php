@extends('layouts.video-room')

@section('content')
    @if ($needsConsent ?? false)
        <div class="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-10">
            <div class="w-full max-w-lg rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-xl">
                <p class="text-xs font-bold uppercase tracking-wider text-teal-300">{{ $roleLabel ?? __('Participante') }}</p>
                <h1 class="mt-2 text-xl font-bold text-white">{{ __('Antes de entrar na sala') }}</h1>

                @if ($isObserver ?? false)
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                        {{ __('Como observador, você entrará em modo silencioso (sem áudio nem vídeo) para acompanhar a sessão.') }}
                    </p>
                @else
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                        {{ __('Leia e confirme os termos abaixo para aceder à consulta online.') }}
                    </p>
                @endif

                @if (session('status'))
                    <p class="mt-4 rounded-lg bg-emerald-500/10 px-3 py-2 text-sm text-emerald-200">{{ session('status') }}</p>
                @endif

                @if ($errors->any())
                    <ul class="mt-4 list-inside list-disc text-sm text-rose-300">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif

                <form method="post" action="{{ $consentUrl }}" class="mt-6 space-y-4">
                    @csrf
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/10 bg-slate-800/60 p-4">
                        <input type="checkbox" name="join_consent" value="1" required class="mt-0.5 rounded border-slate-600 text-teal-500 focus:ring-teal-500" />
                        <span class="text-xs leading-relaxed text-slate-200">
                            {{ __('Confirmo que tenho autorização para participar desta sessão e aceito as regras de privacidade do serviço.') }}
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-950/30 p-4">
                        <input type="checkbox" name="recording_consent" value="1" class="mt-0.5 rounded border-amber-600 text-amber-500 focus:ring-amber-500" />
                        <span class="text-xs leading-relaxed text-amber-100">
                            {{ __('Autorizo a gravação desta sessão para fins clínicos e processamento com IA (transcrição e devolutiva), conforme LGPD.') }}
                        </span>
                    </label>

                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:from-teal-500 hover:to-emerald-500">
                        {{ __('Entrar na sala') }}
                    </button>
                </form>
            </div>
        </div>
    @else
        <div
            class="flex h-full min-h-screen flex-col"
            x-data="guestVideoRoom({
                roomName: @js($videoCall->room_name),
                jitsiDomain: @js($jitsiDomain),
                displayName: @js($displayName),
                startAudioMuted: @js($jitsiConfig['startAudioMuted'] ?? false),
                startVideoMuted: @js($jitsiConfig['startVideoMuted'] ?? false),
                isObserver: @js($isObserver ?? false),
            })"
            data-test="session-video-guest"
        >
            <header class="flex shrink-0 items-center justify-between gap-3 border-b border-white/10 bg-slate-900/95 px-4 py-3 sm:px-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-teal-300">
                        @if ($isObserver ?? false)
                            {{ __('Escuta / supervisão') }}
                        @elseif ($isFamilyGuest ?? false)
                            {{ __('Sessão de casal/família') }}
                        @elseif ($isGroupMember ?? false)
                            {{ __('Grupo terapêutico') }}
                        @else
                            {{ __('Consulta online') }}
                        @endif
                    </p>
                    <h1 class="text-sm font-bold text-white">{{ $displayName }}</h1>
                    <p class="text-xs text-slate-400">
                        @if ($isObserver ?? false)
                            {{ __('Modo observador — áudio e vídeo desligados.') }}
                        @else
                            {{ __('Aguarde o profissional na chamada.') }}
                        @endif
                    </p>
                </div>
            </header>

            <div class="relative min-h-0 flex-1 bg-black">
                <div id="jitsi-container" class="absolute inset-0"></div>
            </div>
        </div>
    @endif
@endsection

@if (! ($needsConsent ?? false))
    @push('head')
        <script src="https://{{ $jitsiDomain }}/external_api.js" async></script>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('guestVideoRoom', (config) => ({
                    init() {
                        this.waitForJitsi().then(() => {
                            const api = new JitsiMeetExternalAPI(config.jitsiDomain, {
                                roomName: config.roomName,
                                parentNode: document.getElementById('jitsi-container'),
                                userInfo: { displayName: config.displayName },
                                configOverwrite: {
                                    prejoinPageEnabled: !config.isObserver,
                                    startWithAudioMuted: config.startAudioMuted,
                                    startWithVideoMuted: config.startVideoMuted,
                                    disableModeratorIndicator: config.isObserver,
                                },
                                interfaceConfigOverwrite: {
                                    SHOW_JITSI_WATERMARK: false,
                                },
                            });

                            if (config.isObserver) {
                                api.addListener('videoConferenceJoined', () => {
                                    api.executeCommand('toggleAudio');
                                    api.executeCommand('toggleVideo');
                                });
                            }
                        });
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
                }));
            });
        </script>
    @endpush
@endif
