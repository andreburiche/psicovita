<x-app-layout>
    <x-slot name="header">{{ __('Relatório de acessibilidade') }}</x-slot>

    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-hero
            :title="__('Contraste de cores (WCAG AA)')"
            :subtitle="$wcagNote"
            icon="eye"
            iconTone="indigo"
        />

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Par') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Amostra') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Rácio') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('AA normal') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('AA grande') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach ($results as $row)
                        <tr>
                            <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $row['label'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-block rounded-lg px-3 py-1.5 text-sm font-semibold"
                                    style="color: {{ $row['foreground'] }}; background-color: {{ $row['background'] }};"
                                >{{ __('Texto de exemplo') }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono tabular-nums">{{ number_format($row['ratio'], 2) }}:1</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-900' => $row['passes_aa_normal'],
                                    'bg-rose-100 text-rose-900' => ! $row['passes_aa_normal'],
                                ])>{{ $row['passes_aa_normal'] ? __('Passa') : __('Falha') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-900' => $row['passes_aa_large'],
                                    'bg-rose-100 text-rose-900' => ! $row['passes_aa_large'],
                                ])>{{ $row['passes_aa_large'] ? __('Passa') : __('Falha') }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
