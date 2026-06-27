@props(['patient', 'documentType'])

@php
    use App\Enums\PatientClinicalDocumentType;

    $tips = match ($documentType) {
        PatientClinicalDocumentType::Atestado => [
            __('Escolha comparecimento para consulta pontual ou afastamento para ausência prolongada.'),
            __('No afastamento, ajuste dias ou datas — o período é calculado automaticamente.'),
            __('Use «Pré-visualizar» para conferir o PDF antes de arquivar na ficha.'),
        ],
        PatientClinicalDocumentType::Declaracao => [
            __('Use linguagem clara e objetiva, sem detalhes clínicos sensíveis desnecessários.'),
            __('O assunto aparece no cabeçalho do PDF quando preenchido.'),
            __('A logo da instituição pode ser configurada no seu perfil profissional.'),
        ],
        PatientClinicalDocumentType::Receita => [
            __('Indique medicamento, dose e horário — um item por linha.'),
            __('Prescrição é de responsabilidade do profissional habilitado.'),
            __('Verifique normas do seu conselho antes de emitir.'),
        ],
    };
@endphp

<aside class="space-y-5 lg:sticky lg:top-24">
    <div class="overflow-hidden rounded-2xl border border-violet-200/80 bg-gradient-to-br from-violet-50/80 via-white to-indigo-50/40 p-5 shadow-sm ring-1 ring-violet-100 dark:border-violet-900/40 dark:from-violet-950/30 dark:via-slate-900/80 dark:to-indigo-950/20 dark:ring-violet-950">
        <p class="text-xs font-bold uppercase tracking-wider text-violet-800 dark:text-violet-300">{{ __('Antes de gerar') }}</p>
        <ul class="mt-3 space-y-2.5">
            @foreach ($tips as $tip)
                <li class="flex gap-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                    <x-ui.icon name="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-violet-500" />
                    <span>{{ $tip }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Paciente') }}</p>
        <div class="mt-3 flex items-center gap-3">
            <x-patient-avatar :patient="$patient" size="sm" class="shrink-0 ring-2 ring-violet-100 dark:ring-violet-900/50" />
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $patient->name }}</p>
                @if ($patient->birth_date)
                    <p class="text-xs text-slate-500">{{ __('Nasc.') }} {{ $patient->birth_date->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/40">
        <p class="flex items-start gap-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
            <x-ui.icon name="document-text" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
            {{ __('O documento será arquivado na aba Documentos da ficha e poderá ser baixado novamente a qualquer momento.') }}
        </p>
    </div>
</aside>
