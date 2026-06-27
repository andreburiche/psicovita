<x-app-layout>
    <x-slot name="header">{{ __('Auditoria do sistema') }}</x-slot>

    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-hero
            :title="__('Registros append-only')"
            :subtitle="__('Trilha de auditoria persistida para ações sensíveis (LGPD).')"
            icon="shield"
            iconTone="indigo"
        />

        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <div class="min-w-[10rem] flex-1">
                <x-input-label for="q" :value="__('Buscar')" />
                <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Ação, entidade ou utilizador…') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" />
            </div>
            <div>
                <x-input-label for="entity" :value="__('Entidade')" />
                <input id="entity" name="entity" type="text" value="{{ $filters['entity'] ?? '' }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" />
            </div>
            <div>
                <x-input-label for="action" :value="__('Ação')" />
                <input id="action" name="action" type="text" value="{{ $filters['action'] ?? '' }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" />
            </div>
            <div>
                <x-input-label for="from" :value="__('De')" />
                <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" />
            </div>
            <div>
                <x-input-label for="to" :value="__('Até')" />
                <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" />
            </div>
            <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
            <a
                href="{{ route('admin.lgpd.audit.export', request()->query()) }}"
                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            >
                {{ __('Exportar CSV') }}
            </a>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Data') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Ação') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Entidade') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Sujeito') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Utilizador') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $log->action }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $log->entity }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $log->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">{{ __('Nenhum registo encontrado.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </div>
</x-app-layout>
