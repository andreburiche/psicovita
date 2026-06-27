@php
    use App\Enums\AiRequestStatus;
    use App\Enums\AiRequestType;

    $modalities = [
        'online' => __('Online'),
        'presencial' => __('Presencial'),
        'ambos' => __('Ambos'),
    ];
    $priceRanges = [
        'ate_100' => __('Até R$ 100'),
        'de_100_200' => __('R$ 100 a R$ 200'),
        'de_200_300' => __('R$ 200 a R$ 300'),
        'acima_300' => __('Acima de R$ 300'),
    ];
    $approachesRec = [
        'sem_preferencia' => __('Sem preferência'),
        'freudiana' => __('Freudiana'),
        'lacaniana' => __('Lacaniana'),
        'jungiana' => __('Jungiana'),
        'tcc' => __('TCC'),
        'humanista' => __('Humanista'),
    ];

    $highlightCopy = '';
    if (isset($highlightRequest) && $highlightRequest?->output_text) {
        if ($highlightRequest->type === AiRequestType::RecomendacaoTerapeuta) {
            $decoded = json_decode($highlightRequest->output_text, true);
            if (is_array($decoded)) {
                foreach ($decoded as $i => $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $highlightCopy .= '#'.($i + 1).' '.$row['name'].' — '.($row['compatibility'] ?? '')."%\n";
                    $highlightCopy .= ($row['specialty'] ?? '').' | '.($row['approach'] ?? '')."\n";
                    $highlightCopy .= ($row['justification'] ?? '')."\n\n";
                }
            } else {
                $highlightCopy = $highlightRequest->output_text;
            }
        } else {
            $highlightCopy = $highlightRequest->output_text;
        }
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        {{ __('IA Assistente') }}
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
        <x-page-hero
            :title="__('IA Assistente')"
            :subtitle="__('Apoio inteligente para transcrição, textos terapêuticos e recomendação de profissionais. A IA não substitui decisão clínica.')"
            icon="sparkles"
            iconTone="indigo"
        >
            <x-slot name="eyebrow">{{ __('Clínica') }}</x-slot>
            <x-slot name="actions">
                <a
                    href="#nova-analise"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                >
                    <x-ui.icon name="sparkles" class="h-5 w-5" />
                    {{ __('Nova análise') }}
                </a>
            </x-slot>
        </x-page-hero>

        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/40 sm:p-8">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => __('Análises hoje'), 'value' => $metrics['analyses_today']],
                    ['label' => __('Textos gerados'), 'value' => $metrics['texts']],
                    ['label' => __('Transcrições'), 'value' => $metrics['transcripts']],
                    ['label' => __('Recomendações'), 'value' => $metrics['recommendations']],
                ] as $m)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-600 dark:bg-slate-800/80">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $m['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $m['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($highlightRequest && $highlightRequest->output_text)
            <div id="ultimo-resultado" class="scroll-mt-24">
                <x-ai-card :title="__('Último resultado')" badge="{{ $highlightRequest->type->label() }}">
                    <x-ai-result-box>
                        @if ($highlightRequest->type === AiRequestType::RecomendacaoTerapeuta)
                            @php
                                $rows = json_decode($highlightRequest->output_text, true);
                            @endphp
                            @if (is_array($rows))
                                <ul class="space-y-3">
                                    @foreach ($rows as $i => $row)
                                        @if (is_array($row))
                                            <li class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-600 dark:bg-slate-900">
                                                <p class="font-bold text-slate-900 dark:text-white">{{ $row['name'] ?? '' }}</p>
                                                <p class="text-xs text-slate-600 dark:text-slate-400">{{ $row['specialty'] ?? '' }} · {{ $row['approach'] ?? '' }}</p>
                                                <p class="mt-2 text-sm font-semibold text-indigo-700 dark:text-indigo-300">{{ $row['compatibility'] ?? '—' }}% {{ __('compatibilidade') }}</p>
                                                <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ $row['justification'] ?? '' }}</p>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @else
                                <pre class="whitespace-pre-wrap text-xs">{{ e($highlightRequest->output_text) }}</pre>
                            @endif
                        @else
                            <div class="whitespace-pre-wrap text-sm">{{ e($highlightRequest->output_text) }}</div>
                        @endif
                    </x-ai-result-box>

                    <textarea id="highlight-copy" class="sr-only" readonly>{{ $highlightCopy }}</textarea>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                            onclick="navigator.clipboard.writeText(document.getElementById('highlight-copy').value)"
                        >
                            {{ __('Copiar') }}
                        </button>
                    </div>

                    @if ($patients->isNotEmpty())
                        <form action="{{ route('ai.save-record') }}" method="POST" class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-600 dark:bg-slate-900">
                            @csrf
                            <input type="hidden" name="ai_request_id" value="{{ $highlightRequest->id }}" />
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Salvar no prontuário') }}</p>
                            <select name="patient_id" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">{{ __('Selecione o paciente') }}</option>
                                @foreach ($patients as $p)
                                    <option value="{{ $p->id }}" @selected($highlightRequest->patient_id === $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="inline-flex justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('Salvar no prontuário') }}</button>
                        </form>
                    @else
                        <p class="text-xs text-slate-500">{{ __('Cadastre um paciente para poder guardar no prontuário.') }}</p>
                    @endif
                </x-ai-card>
            </div>
        @endif

        <div id="nova-analise" class="scroll-mt-24 space-y-8">
            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                    {{-- Card 1 --}}
                    <x-ai-card :title="__('Transcrever sessão')" :subtitle="__('Envio de áudio com consentimento explícito.')" class="xl:col-span-1">
                        <x-ai.transcribe-form :patients="$patients" />
                    </x-ai-card>

                    {{-- Card 2 --}}
                    <x-ai-card :title="__('Gerar texto por abordagem')" :subtitle="__('Respostas éticas, sem diagnóstico fechado.')">
                        <x-ai.generate-text-form :patients="$patients" />
                    </x-ai-card>

                    {{-- Card 3 spans full width on xl row --}}
                    <x-ai-card :title="__('Recomendação de terapeuta')" :subtitle="__('Ranking ilustrativo — sem promessa de resultado.')" class="lg:col-span-2 xl:col-span-1">
                        <form action="{{ route('ai.recommend-therapist') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label for="complaint" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Principal queixa do paciente') }}</label>
                                <textarea name="complaint" id="complaint" rows="4" required class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('complaint') }}</textarea>
                                @error('complaint')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <x-form-select name="modality" :label="__('Preferência de atendimento')" :options="$modalities" :value="old('modality')" :required="true" />
                            <x-form-select name="price_range" :label="__('Faixa de valor')" :options="$priceRanges" :value="old('price_range')" :required="true" />
                            <x-form-select name="approach" :label="__('Abordagem desejada')" :options="$approachesRec" :value="old('approach')" :required="true" />
                            <div>
                                <label for="availability" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Disponibilidade') }}</label>
                                <input type="text" name="availability" id="availability" value="{{ old('availability') }}" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" maxlength="500" placeholder="{{ __('Ex.: tardes de terça e quinta') }}" />
                            </div>
                            <button type="submit" class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">{{ __('Recomendar terapeuta') }}</button>
                        </form>
                    </x-ai-card>
            </div>

            <section class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-br from-white via-slate-50/90 to-indigo-50/40 p-4 shadow-lg shadow-indigo-950/5 ring-1 ring-slate-200/70 dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/30 dark:shadow-black/25 dark:ring-slate-700/80 sm:p-6">
                <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-indigo-400/10 blur-3xl dark:bg-indigo-500/10" aria-hidden="true"></div>
                <div class="relative flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-3 text-base font-bold tracking-tight text-slate-900 dark:text-white">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/25 dark:bg-indigo-500 dark:shadow-indigo-900/40" aria-hidden="true">
                                <x-ui.icon name="clock" class="h-5 w-5" />
                            </span>
                            {{ __('Histórico da IA') }}
                        </h2>
                        <p class="mt-2 max-w-xl text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Registros de uso — pode excluir a qualquer momento.') }}</p>
                    </div>
                </div>

                <div class="relative mt-5 overflow-hidden rounded-xl border border-slate-200/90 bg-white/95 shadow-inner dark:border-slate-700 dark:bg-slate-950/50">
                    <div class="max-h-[32rem] overflow-x-auto overflow-y-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead class="sticky top-0 z-[1] border-b border-slate-200/90 bg-slate-50/95 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/95">
                                <tr class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    <th scope="col" class="px-4 py-3.5">{{ __('Tipo') }}</th>
                                    <th scope="col" class="px-4 py-3.5">{{ __('Paciente') }}</th>
                                    <th scope="col" class="px-4 py-3.5">{{ __('Abord.') }}</th>
                                    <th scope="col" class="px-4 py-3.5">{{ __('Estado') }}</th>
                                    <th scope="col" class="px-4 py-3.5">{{ __('Data') }}</th>
                                    <th scope="col" class="px-4 py-3.5 text-right">{{ __('Ação') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($recent as $r)
                                    @php
                                        $statusStyles = match ($r->status) {
                                            AiRequestStatus::Completed => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-500/25',
                                            AiRequestStatus::Failed => 'bg-rose-50 text-rose-800 ring-rose-600/20 dark:bg-rose-950/50 dark:text-rose-200 dark:ring-rose-500/25',
                                            AiRequestStatus::Pending => 'bg-amber-50 text-amber-900 ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-100 dark:ring-amber-500/25',
                                        };
                                    @endphp
                                    <tr class="transition-colors hover:bg-indigo-50/60 dark:hover:bg-indigo-950/25">
                                        <td class="px-4 py-3.5 align-middle">
                                            <span class="inline-flex max-w-[11rem] truncate font-medium text-slate-800 dark:text-slate-100" title="{{ $r->type->label() }}">{{ $r->type->label() }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 align-middle text-slate-600 dark:text-slate-300">{{ $r->patient?->name ?? '—' }}</td>
                                        <td class="px-4 py-3.5 align-middle text-slate-600 dark:text-slate-400">{{ $r->approach ?? '—' }}</td>
                                        <td class="px-4 py-3.5 align-middle">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusStyles }}">{{ $r->status->label() }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3.5 align-middle tabular-nums text-slate-500 dark:text-slate-400">{{ $r->created_at?->format('d/m H:i') }}</td>
                                        <td class="px-4 py-3.5 align-middle">
                                            <div class="flex items-center justify-end gap-1.5">
                                                @if ($r->output_text)
                                                    <a
                                                        href="{{ route('ai.show', $r) }}"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-transparent text-indigo-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-indigo-400 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/80 dark:hover:text-indigo-300 dark:focus:ring-offset-slate-900"
                                                        title="{{ __('Ver detalhe') }}"
                                                    >
                                                        <span class="sr-only">{{ __('Ver') }}</span>
                                                        <x-ui.icon name="eye" class="h-5 w-5" />
                                                    </a>
                                                @else
                                                    <span
                                                        class="inline-flex h-9 w-9 cursor-default items-center justify-center rounded-xl border border-dashed border-slate-200 text-slate-300 dark:border-slate-700 dark:text-slate-600"
                                                        title="{{ __('Sem resultado para visualizar') }}"
                                                        aria-label="{{ __('Sem resultado para visualizar') }}"
                                                    >
                                                        <x-ui.icon name="ban" class="h-5 w-5" />
                                                    </span>
                                                @endif
                                                <x-confirm-form
                                                    action="{{ route('ai.destroy', $r) }}"
                                                    method="POST"
                                                    :title="__('Excluir registro?')"
                                                    :message="__('O histórico desta solicitação de IA será removido.')"
                                                    :confirm-label="__('Sim, excluir')"
                                                    variant="danger"
                                                    :validate="false"
                                                    class="inline"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-transparent text-rose-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 dark:text-rose-400 dark:hover:border-rose-900 dark:hover:bg-rose-950/60 dark:hover:text-rose-300 dark:focus:ring-offset-slate-900"
                                                        title="{{ __('Excluir registro') }}"
                                                    >
                                                        <span class="sr-only">{{ __('Excluir') }}</span>
                                                        <x-ui.icon name="trash" class="h-5 w-5" />
                                                    </button>
                                                </x-confirm-form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-14 text-center">
                                            <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                                    <x-ui.icon name="archive" class="h-7 w-7" />
                                                </span>
                                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Ainda sem pedidos.') }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Use os cartões acima para transcrever, gerar texto ou pedir recomendações.') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
