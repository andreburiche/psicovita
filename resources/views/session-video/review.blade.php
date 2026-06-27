<x-app-layout>
    <x-slot name="header">{{ __('Revisão pós-sessão') }}</x-slot>

    @php
        $patient = $session->patient;
        $isProcessing = $videoCall->isProcessing();
        $isReady = $videoCall->isReadyForReview();
        $isFailed = $videoCall->recording_status === \App\Enums\VideoRecordingStatus::Failed;
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8" data-test="session-video-review">
            <x-page-hero
                :title="__('Revisão da sessão por vídeo')"
                :subtitle="__('Transcrição e devolutiva geradas com apoio da IA — revisão profissional obrigatória.')"
                icon="video"
                iconTone="indigo"
            >
                <x-slot name="eyebrow">{{ $patient->name }} · {{ $session->session_date->format('d/m/Y') }}</x-slot>
                <x-slot name="actions">
                    <a href="{{ route('therapy-sessions.show', $session) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                        <x-ui.icon name="arrow-left" class="h-4 w-4" />
                        {{ __('Voltar à sessão') }}
                    </a>
                </x-slot>
            </x-page-hero>

            @if (session('status'))
                <x-ui.success-alert :title="session('status')" />
            @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Estado da chamada') }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $videoCall->status->label() }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Gravação / IA') }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $videoCall->recording_status->label() }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Abordagem') }}</p>
                    <p class="mt-2 text-sm font-semibold uppercase text-slate-900 dark:text-white">{{ $videoCall->approach }}</p>
                </div>
            </div>

            @if ($isProcessing)
                <div class="rounded-2xl border border-violet-200 bg-violet-50 p-6 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-100">
                    <p>{{ __('Processando gravação com IA… Esta página atualiza automaticamente.') }}</p>
                </div>
                @push('scripts')
                    <script>setTimeout(() => window.location.reload(), 5000);</script>
                @endpush
            @endif

            @if ($isFailed)
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                    {{ $videoCall->processing_error ?? __('Falha ao processar a gravação.') }}
                </div>
            @endif

            @if ($isReady)
                <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                    <div class="space-y-6 lg:col-span-8">
                        <x-clinical-documents.partials.section-card
                            :title="__('Transcrição da sessão')"
                            :description="__('Texto gerado a partir da gravação de áudio. Confira nomes e termos clínicos.')"
                            icon="chat-bubble-left-right"
                            tone="violet"
                        >
                            <textarea readonly rows="12" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm leading-relaxed dark:border-slate-600 dark:bg-slate-900">{{ $videoCall->transcription_text }}</textarea>
                            <form method="post" action="{{ route('therapy-sessions.video.save-record', $session) }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="content_type" value="transcription" />
                                <button type="submit" class="text-sm font-semibold text-violet-600 hover:text-violet-500">{{ __('Arquivar transcrição no prontuário') }}</button>
                            </form>
                        </x-clinical-documents.partials.section-card>

                        <x-clinical-documents.partials.section-card
                            :title="__('Resumo clínico')"
                            :description="__('Síntese para o profissional, alinhada à abordagem escolhida.')"
                            icon="clipboard-list"
                            tone="indigo"
                        >
                            <textarea readonly rows="10" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm leading-relaxed dark:border-slate-600 dark:bg-slate-900">{{ $videoCall->clinical_summary_text }}</textarea>
                            <form method="post" action="{{ route('therapy-sessions.video.save-record', $session) }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="content_type" value="clinical_summary" />
                                <button type="submit" class="text-sm font-semibold text-violet-600 hover:text-violet-500">{{ __('Arquivar resumo no prontuário') }}</button>
                            </form>
                        </x-clinical-documents.partials.section-card>

                        <x-clinical-documents.partials.section-card
                            :title="__('Devolutiva ao paciente')"
                            :description="__('Texto acolhedor para compartilhar com quem foi atendido na chamada.')"
                            icon="chat-bubble-left-right"
                            tone="teal"
                        >
                            <textarea readonly rows="10" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm leading-relaxed dark:border-slate-600 dark:bg-slate-900">{{ $videoCall->devolutiva_patient_text }}</textarea>

                            <form method="post" action="{{ route('therapy-sessions.video.regenerate-devolutiva', $session) }}" class="mt-4 flex flex-wrap items-end gap-3">
                                @csrf
                                <div>
                                    <label for="regen_approach" class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('Regenerar com abordagem') }}</label>
                                    <select id="regen_approach" name="approach" class="mt-1 block rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                                        @foreach (['tcc', 'humanista', 'freudiana', 'lacaniana', 'jungiana', 'winnicottiana', 'sistemica'] as $approach)
                                            <option value="{{ $approach }}" @selected($videoCall->approach === $approach)>{{ strtoupper($approach) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">{{ __('Regenerar devolutiva') }}</button>
                            </form>

                            <form method="post" action="{{ route('therapy-sessions.video.save-record', $session) }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="content_type" value="devolutiva" />
                                <button type="submit" class="text-sm font-semibold text-violet-600 hover:text-violet-500">{{ __('Arquivar devolutiva no prontuário') }}</button>
                            </form>
                        </x-clinical-documents.partials.section-card>

                        <form method="post" action="{{ route('therapy-sessions.video.save-record', $session) }}">
                            @csrf
                            <input type="hidden" name="content_type" value="full" />
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:from-violet-500 hover:to-indigo-500 sm:w-auto">
                                <x-ui.icon name="document-text" class="h-4 w-4" />
                                {{ __('Arquivar pacote completo no prontuário') }}
                            </button>
                        </form>
                    </div>

                    <aside class="space-y-5 lg:col-span-4 lg:sticky lg:top-24">
                        <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Paciente') }}</p>
                            <div class="mt-3 flex items-center gap-3">
                                <x-patient-avatar :patient="$patient" size="sm" class="shrink-0 ring-2 ring-violet-100" />
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $patient->name }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Link do paciente') }}</p>
                            <p class="mt-2 break-all text-xs text-slate-600 dark:text-slate-300">{{ $guestJoinUrl }}</p>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
