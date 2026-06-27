@props([
    'activeTab',
])

@php
    $context = match ($activeTab) {
        'clinical-records' => [
            'icon' => 'document-text',
            'tone' => 'violet',
            'title' => __('Prontuário clínico'),
            'subtitle' => __('Histórico encriptado de registros deste paciente.'),
        ],
        'payments' => [
            'icon' => 'currency',
            'tone' => 'emerald',
            'title' => __('Financeiro'),
            'subtitle' => __('Pagamentos, pendências e totais vinculados às sessões.'),
        ],
        'document-requests' => [
            'icon' => 'document-text',
            'tone' => 'sky',
            'title' => __('Documentos e solicitações'),
            'subtitle' => __('Anexos na ficha e pedidos formais a instituições.'),
        ],
        'assessments' => [
            'icon' => 'chart-bar',
            'tone' => 'indigo',
            'title' => __('Avaliações e indicadores'),
            'subtitle' => __('Escalas clínicas, evolução longitudinal, objetivos e indicadores de risco.'),
        ],
        default => null,
    };
@endphp

@if ($context)
    <x-ui.section-heading
        :icon="$context['icon']"
        :icon-tone="$context['tone']"
        :title="$context['title']"
        :subtitle="$context['subtitle']"
        class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/60 dark:ring-slate-700/50"
    />
@endif
