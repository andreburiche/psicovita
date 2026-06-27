@props([
    'session',
    'canUseVideoConference' => true,
    'variant' => 'card',
])

@php
    use App\Enums\TherapySessionStatus;
    use App\Enums\TherapySessionType;
    use App\Enums\VideoCallStatus;

    $isCancelled = $session->status === TherapySessionStatus::Cancelled;
    $isOnline = $session->type === TherapySessionType::Online;
    $roomUrl = route('therapy-sessions.video.room', $session);
    $reviewUrl = route('therapy-sessions.video.review', $session);
    $isLive = $session->videoCall?->status === VideoCallStatus::Live;
@endphp

@if (! $isCancelled)
    @if ($variant === 'button')
        <a
            href="{{ $roomUrl }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-indigo-300/80 bg-indigo-500/20 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500/30"
        >
            <x-ui.icon name="video" class="h-4 w-4 shrink-0" />
            {{ $isLive ? __('Retomar vídeo') : __('Videoconferência') }}
        </a>
    @else
        <section class="overflow-hidden rounded-2xl border border-indigo-200/90 bg-gradient-to-br from-indigo-50/80 via-white to-violet-50/40 shadow-lg ring-1 ring-indigo-100 dark:border-indigo-900/50 dark:from-indigo-950/40 dark:via-slate-900/80 dark:to-violet-950/30 dark:ring-indigo-950" data-test="session-video-conference">
            <div class="border-b border-indigo-100/80 px-5 py-4 dark:border-indigo-900/40">
                <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-200">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/15 dark:text-indigo-300">
                        <x-ui.icon name="video" class="h-4 w-4" />
                    </span>
                    {{ __('Videoconferência') }}
                </h2>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                    {{ __('Sala tipo Meet/Zoom · gravação · transcrição e devolutiva com IA.') }}
                </p>
            </div>
            <div class="space-y-3 p-5">
                @if (! $isOnline)
                    <p class="rounded-xl border border-amber-200/80 bg-amber-50/80 px-3 py-2 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                        {{ __('Modalidade «Presencial» — pode usar vídeo na mesma ou altere para «Online» ao editar a sessão.') }}
                    </p>
                @endif

                @unless ($canUseVideoConference)
                    <p class="rounded-xl border border-violet-200/80 bg-violet-50/80 px-3 py-2 text-xs text-violet-900 dark:border-violet-900/50 dark:bg-violet-950/30 dark:text-violet-100">
                        {{ __('Gravação e IA pós-sessão exigem plano com assistente clínico.') }}
                        <a href="{{ route('subscription.checkout') }}" class="font-semibold underline">{{ __('Ver planos') }}</a>
                    </p>
                @endunless

                @if ($session->videoCall?->isReadyForReview() && $canUseVideoConference)
                    <a href="{{ $reviewUrl }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-4 py-3 text-sm font-semibold text-indigo-800 shadow-sm transition hover:bg-indigo-50 dark:border-indigo-800 dark:bg-slate-900 dark:text-indigo-200">
                        <x-ui.icon name="document-text" class="h-5 w-5" />
                        {{ __('Ver transcrição e devolutiva') }}
                    </a>
                @endif

                <a
                    href="{{ $roomUrl }}"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:from-indigo-500 hover:to-violet-500"
                >
                    <x-ui.icon name="video" class="h-5 w-5" />
                    {{ $isLive ? __('Retomar sala de vídeo') : __('Iniciar videoconferência') }}
                </a>

                @if ($session->videoCall)
                    <p class="text-center text-[11px] text-slate-500 dark:text-slate-400">
                        {{ __('Estado:') }} {{ $session->videoCall->status->label() }}
                        · {{ $session->videoCall->recording_status->label() }}
                    </p>
                @endif
            </div>
        </section>
    @endif
@endif
