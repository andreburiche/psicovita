@php
    $statusStyles = match ($aiRequest->status) {
        \App\Enums\AiRequestStatus::Completed => [
            'badge' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-500/25',
            'icon' => 'check-circle',
            'tone' => 'emerald',
        ],
        \App\Enums\AiRequestStatus::Failed => [
            'badge' => 'bg-rose-50 text-rose-800 ring-rose-600/20 dark:bg-rose-950/50 dark:text-rose-200 dark:ring-rose-500/25',
            'icon' => 'alert-circle',
            'tone' => 'rose',
        ],
        default => [
            'badge' => 'bg-amber-50 text-amber-900 ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-100 dark:ring-amber-500/25',
            'icon' => 'clock',
            'tone' => 'amber',
        ],
    };

    $typeIcon = match ($aiRequest->type) {
        \App\Enums\AiRequestType::Transcricao => 'sparkles',
        \App\Enums\AiRequestType::TextoAbordagem => 'document-text',
        \App\Enums\AiRequestType::RecomendacaoTerapeuta => 'users',
    };

    $inputMeta = json_decode($aiRequest->input_text ?? '', true);
    $inputMeta = is_array($inputMeta) ? $inputMeta : [];

    $sessionTypeLabels = [
        'primeira_sessao' => __('Primeira sessão'),
        'retorno' => __('Retorno'),
        'avaliacao_inicial' => __('Avaliação inicial'),
    ];

    $copyText = $aiRequest->output_text ?? '';
    if ($aiRequest->type === \App\Enums\AiRequestType::RecomendacaoTerapeuta && filled($copyText)) {
        $decoded = json_decode($copyText, true);
        if (is_array($decoded)) {
            $copyText = '';
            foreach ($decoded as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $copyText .= '#'.($i + 1).' '.($row['name'] ?? '').' — '.($row['compatibility'] ?? '')."%\n";
                $copyText .= ($row['specialty'] ?? '').' | '.($row['approach'] ?? '')."\n";
                $copyText .= ($row['justification'] ?? '')."\n\n";
            }
        }
    }

    $hasOutput = filled($aiRequest->output_text);
    $canSaveToRecord = $aiRequest->status === \App\Enums\AiRequestStatus::Completed && $hasOutput && $aiRequest->type !== \App\Enums\AiRequestType::RecomendacaoTerapeuta;
@endphp

<x-app-layout>
    <x-slot name="header">
        {{ __('Registro de IA') }}
    </x-slot>

    <div
        class="mx-auto max-w-6xl space-y-6 px-4 pb-12 sm:px-6 lg:px-8"
        x-data="{
            copied: false,
            copyText: @js($copyText),
            copy() {
                if (! this.copyText) return;
                navigator.clipboard.writeText(this.copyText).then(() => {
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2200);
                });
            }
        }"
    >
        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400" aria-label="{{ __('Navegação') }}">
            <a href="{{ route('ai.index') }}" class="font-medium text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                {{ __('IA Assistente') }}
            </a>
            <x-ui.icon name="chevron-right" class="h-4 w-4 shrink-0 opacity-50" />
            <span class="font-medium text-slate-700 dark:text-slate-300">{{ __('Registro #:id', ['id' => $aiRequest->id]) }}</span>
        </nav>

        {{-- Hero --}}
        <div class="relative overflow-hidden rounded-3xl border border-indigo-200/70 bg-gradient-to-br from-white via-indigo-50/40 to-violet-50/60 p-6 shadow-lg shadow-indigo-900/5 ring-1 ring-indigo-100/70 dark:border-indigo-900/40 dark:from-slate-900 dark:via-indigo-950/30 dark:to-violet-950/20 dark:ring-indigo-900/30 sm:p-8">
            <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-indigo-400/15 blur-3xl dark:bg-indigo-500/10" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 items-start gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-600/30">
                        <x-ui.icon :name="$typeIcon" class="h-7 w-7" />
                    </span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-indigo-700 ring-1 ring-indigo-200/80 dark:bg-indigo-950/50 dark:text-indigo-200 dark:ring-indigo-800/50">
                                {{ $aiRequest->type->label() }}
                            </span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusStyles['badge'] }}">
                                {{ $aiRequest->status->label() }}
                            </span>
                        </div>
                        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                            {{ __('Resultado da análise') }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            {{ $aiRequest->created_at?->translatedFormat('l, d \d\e F \d\e Y · H:i') }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <a
                        href="{{ route('ai.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200/90 bg-white/90 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-white dark:border-slate-600 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        <x-ui.icon name="arrow-left" class="h-4 w-4" />
                        {{ __('Voltar') }}
                    </a>
                    @if ($hasOutput)
                        <button
                            type="button"
                            @click="copy()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/25 transition hover:bg-indigo-500"
                        >
                            <x-ui.icon name="clipboard" class="h-4 w-4" />
                            <span x-text="copied ? @js(__('Copiado!')) : @js(__('Copiar texto'))"></span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if (session('status'))
            <x-ui.success-alert :title="session('status')" />
        @endif

        <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
            {{-- Sidebar metadados --}}
            <aside class="space-y-4 lg:col-span-4 lg:sticky lg:top-6">
                <section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100/80 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Contexto') }}</h2>
                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Paciente') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                @if ($aiRequest->patient)
                                    <a href="{{ route('patients.show', $aiRequest->patient) }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                        {{ $aiRequest->patient->name }}
                                    </a>
                                @else
                                    <span class="text-slate-400">{{ __('Não associado') }}</span>
                                @endif
                            </dd>
                        </div>
                        @if ($aiRequest->approach)
                            <div>
                                <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Abordagem') }}</dt>
                                <dd class="mt-1 text-sm font-semibold capitalize text-slate-900 dark:text-white">{{ $aiRequest->approach }}</dd>
                            </div>
                        @endif
                        @if (! empty($inputMeta['session_type']))
                            <div>
                                <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Tipo de sessão') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ $sessionTypeLabels[$inputMeta['session_type']] ?? $inputMeta['session_type'] }}
                                </dd>
                            </div>
                        @endif
                        @if (! empty($inputMeta['file']))
                            <div>
                                <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Ficheiro de áudio') }}</dt>
                                <dd class="mt-1 break-all text-sm font-medium text-slate-800 dark:text-slate-200">{{ $inputMeta['file'] }}</dd>
                            </div>
                        @endif
                        @if ($aiRequest->tokens_used)
                            <div>
                                <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Tokens utilizados') }}</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($aiRequest->tokens_used, 0, ',', '.') }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                @if ($aiRequest->lgpd_consent_at)
                    <section class="rounded-2xl border border-emerald-200/70 bg-emerald-50/50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                        <div class="flex gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                <x-ui.icon name="shield-check" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-300">{{ __('Consentimento LGPD') }}</p>
                                <p class="mt-1 text-xs leading-relaxed text-emerald-900/80 dark:text-emerald-200/80">
                                    {{ __('Registado em :date', ['date' => $aiRequest->lgpd_consent_at->format('d/m/Y H:i')]) }}
                                </p>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($canSaveToRecord && $patients->isNotEmpty())
                    <section class="rounded-2xl border border-indigo-200/70 bg-white p-5 shadow-sm dark:border-indigo-900/40 dark:bg-slate-900/80">
                        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                            <x-ui.icon name="document-text" class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            {{ __('Salvar no prontuário') }}
                        </h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Cria uma entrada clínica com este texto — revise antes de finalizar.') }}</p>
                        <form action="{{ route('ai.save-record') }}" method="POST" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="ai_request_id" value="{{ $aiRequest->id }}" />
                            <div>
                                <label for="save_patient_id" class="sr-only">{{ __('Paciente') }}</label>
                                <select
                                    id="save_patient_id"
                                    name="patient_id"
                                    required
                                    class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                >
                                    <option value="">{{ __('Selecione o paciente') }}</option>
                                    @foreach ($patients as $patient)
                                        <option value="{{ $patient->id }}" @selected($aiRequest->patient_id === $patient->id)>{{ $patient->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                <x-ui.icon name="plus" class="h-4 w-4" />
                                {{ __('Criar entrada no prontuário') }}
                            </button>
                        </form>
                    </section>
                @endif

                @can('delete', $aiRequest)
                    <section class="rounded-2xl border border-rose-200/60 bg-rose-50/30 p-4 dark:border-rose-900/40 dark:bg-rose-950/20">
                        <x-confirm-form
                            action="{{ route('ai.destroy', $aiRequest) }}"
                            method="POST"
                            :title="__('Remover registro de IA?')"
                            :message="__('O histórico desta solicitação será excluído permanentemente.')"
                            :confirm-label="__('Sim, remover')"
                            variant="danger"
                            :validate="false"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-800 dark:bg-slate-900 dark:text-rose-300 dark:hover:bg-rose-950/40">
                                <x-ui.icon name="trash" class="h-4 w-4" />
                                {{ __('Excluir registro') }}
                            </button>
                        </x-confirm-form>
                    </section>
                @endcan
            </aside>

            {{-- Conteúdo principal --}}
            <main class="lg:col-span-8">
                @if ($aiRequest->status === \App\Enums\AiRequestStatus::Failed)
                    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-rose-200 bg-rose-50/50 px-6 py-16 text-center dark:border-rose-900/50 dark:bg-rose-950/20">
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                            <x-ui.icon name="alert-circle" class="h-8 w-8" />
                        </span>
                        <h2 class="mt-4 text-lg font-bold text-slate-900 dark:text-white">{{ __('Análise não concluída') }}</h2>
                        <p class="mt-2 max-w-md text-sm text-slate-600 dark:text-slate-400">{{ __('Esta solicitação falhou ou não gerou resultado. Tente novamente no módulo IA.') }}</p>
                        <a href="{{ route('ai.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                            {{ __('Nova análise') }}
                        </a>
                    </div>
                @elseif (! $hasOutput)
                    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900/40">
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                            <x-ui.icon name="clock" class="h-8 w-8" />
                        </span>
                        <h2 class="mt-4 text-lg font-bold text-slate-900 dark:text-white">{{ __('Resultado indisponível') }}</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Ainda não há conteúdo gerado para este registro.') }}</p>
                    </div>
                @else
                    <article class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-indigo-50/40 px-5 py-4 dark:border-slate-700 dark:from-slate-900 dark:to-indigo-950/20 sm:px-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">{{ __('Conteúdo gerado') }}</h2>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-[11px] font-semibold text-amber-900 ring-1 ring-amber-200/80 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-800/50">
                                    <x-ui.icon name="alert-triangle" class="h-3.5 w-3.5" />
                                    {{ __('Revisão profissional obrigatória') }}
                                </span>
                            </div>
                        </div>

                        <div class="p-5 sm:p-6">
                            @if ($aiRequest->type === \App\Enums\AiRequestType::RecomendacaoTerapeuta)
                                @php
                                    $rows = json_decode($aiRequest->output_text, true);
                                @endphp
                                @if (is_array($rows) && count($rows) > 0)
                                    @include('ai.partials.recommendation-results', ['rows' => $rows])
                                @else
                                    <pre class="whitespace-pre-wrap rounded-xl bg-slate-50 p-4 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ e($aiRequest->output_text) }}</pre>
                                @endif
                            @else
                                <div class="prose prose-sm max-w-none dark:prose-invert">
                                    <div class="whitespace-pre-wrap rounded-xl border border-slate-100 bg-slate-50/80 p-5 text-sm leading-relaxed text-slate-800 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-100">
                                        {{ $aiRequest->output_text }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($aiRequest->type === \App\Enums\AiRequestType::TextoAbordagem && filled($aiRequest->input_text))
                            <details class="border-t border-slate-100 dark:border-slate-700">
                                <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50 sm:px-6">
                                    {{ __('Ver texto de entrada (resumo)') }}
                                </summary>
                                <div class="border-t border-slate-100 px-5 py-4 dark:border-slate-700 sm:px-6">
                                    <p class="whitespace-pre-wrap text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($aiRequest->input_text, 2000) }}</p>
                                </div>
                            </details>
                        @endif
                    </article>
                @endif
            </main>
        </div>

        <textarea id="ai-output-copy" class="sr-only" readonly>{{ $copyText }}</textarea>
    </div>
</x-app-layout>
