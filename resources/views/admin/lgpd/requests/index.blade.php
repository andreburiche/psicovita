<x-app-layout>
    <x-slot name="header">{{ __('Solicitações LGPD') }}</x-slot>

    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <x-page-hero
                :title="__('Solicitações do titular')"
                :subtitle="__('Gestão de pedidos de direitos LGPD recebidos pelo portal do paciente.')"
                icon="shield"
                iconTone="indigo"
            />
            <a href="{{ route('admin.lgpd.metrics') }}" class="shrink-0 text-sm font-semibold text-violet-600 hover:underline dark:text-violet-400">{{ __('Métricas LGPD') }}</a>
        </div>

        <form method="GET" action="{{ route('admin.lgpd.requests.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <div class="min-w-[10rem] flex-1">
                <x-input-label for="q" :value="__('Buscar titular')" />
                <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Nome ou e-mail…') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" />
            </div>
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('Todos') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="type" :value="__('Tipo')" />
                <select id="type" name="type" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('Todos') }}</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/80">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Data') }}</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Titular') }}</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Tipo') }}</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-300">{{ __('Ações') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($requests as $item)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-400">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900 dark:text-white">{{ $item->user?->name }}</p>
                                <p class="text-xs text-slate-500">{{ $item->user?->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $item->type->label() }}</td>
                            <td class="px-4 py-3">
                                <span @class(['inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', $item->status->badgeClass()])>
                                    {{ $item->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.lgpd.requests.show', $item) }}" class="font-semibold text-violet-600 hover:underline dark:text-violet-400">{{ __('Abrir') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">{{ __('Nenhuma solicitação encontrada.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $requests->links() }}
    </div>
</x-app-layout>
