@props(['patient', 'scaleType', 'definition', 'latestForScale' => null])

@php
    $tips = match ($scaleType) {
        \App\Enums\ClinicalScaleType::Bai => [
            __('Aplique em sessão ou peça ao paciente para responder com orientação.'),
            __('Considere sintomas da última semana, incluindo hoje.'),
            __('Pontuações elevadas indicam triagem — não substituem diagnóstico.'),
        ],
        \App\Enums\ClinicalScaleType::Bdi => [
            __('Foque nos últimos 14 dias, conforme orientação do BDI.'),
            __('Itens sobre humor, energia e sono ajudam a monitorar evolução.'),
            __('Compare resultados na aba Avaliações da ficha.'),
        ],
        \App\Enums\ClinicalScaleType::Stress => [
            __('Avalie a frequência de cada situação nas últimas semanas.'),
            __('Útil para acompanhar sobrecarga em paralelo a outras escalas.'),
            __('Registre observações relevantes ao final do formulário.'),
        ],
    };

    $sidebarTone = match ($scaleType) {
        \App\Enums\ClinicalScaleType::Bdi => 'indigo',
        \App\Enums\ClinicalScaleType::Stress => 'teal',
        default => 'amber',
    };
@endphp

<aside class="space-y-5 lg:sticky lg:top-24">
    <div @class([
        'overflow-hidden rounded-2xl border p-5 shadow-sm ring-1',
        'border-amber-200/80 bg-gradient-to-br from-amber-50/80 via-white to-orange-50/40 ring-amber-100 dark:border-amber-900/40 dark:from-amber-950/30 dark:via-slate-900/80 dark:to-orange-950/20 dark:ring-amber-950' => $sidebarTone === 'amber',
        'border-indigo-200/80 bg-gradient-to-br from-indigo-50/80 via-white to-violet-50/40 ring-indigo-100 dark:border-indigo-900/40 dark:from-indigo-950/30 dark:via-slate-900/80 dark:to-violet-950/20 dark:ring-indigo-950' => $sidebarTone === 'indigo',
        'border-teal-200/80 bg-gradient-to-br from-teal-50/80 via-white to-emerald-50/40 ring-teal-100 dark:border-teal-900/40 dark:from-teal-950/30 dark:via-slate-900/80 dark:to-emerald-950/20 dark:ring-teal-950' => $sidebarTone === 'teal',
    ])>
        <p @class([
            'text-xs font-bold uppercase tracking-wider',
            'text-amber-800 dark:text-amber-300' => $sidebarTone === 'amber',
            'text-indigo-800 dark:text-indigo-300' => $sidebarTone === 'indigo',
            'text-teal-800 dark:text-teal-300' => $sidebarTone === 'teal',
        ])>{{ __('Antes de aplicar') }}</p>
        <ul class="mt-3 space-y-2.5">
            @foreach ($tips as $tip)
                <li class="flex gap-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                    <x-ui.icon name="check" @class([
                        'mt-0.5 h-3.5 w-3.5 shrink-0',
                        'text-amber-500' => $sidebarTone === 'amber',
                        'text-indigo-500' => $sidebarTone === 'indigo',
                        'text-teal-500' => $sidebarTone === 'teal',
                    ]) />
                    <span>{{ $tip }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    @if ($latestForScale)
        <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Última aplicação') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $latestForScale->total_score }}</p>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ $latestForScale->severity_label }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500">{{ $latestForScale->assessed_at->format('d/m/Y') }}</p>
        </div>
    @endif

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
            <x-ui.icon name="chart-bar" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
            {{ __('Pontuação máxima: :max. O resultado será exibido na aba Avaliações com gráfico de evolução.', ['max' => $definition['max_score'] ?? 0]) }}
        </p>
    </div>
</aside>
