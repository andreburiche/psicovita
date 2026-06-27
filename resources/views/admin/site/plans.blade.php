<x-app-layout>
    <x-slot name="header">{{ __('Planos e preços') }}</x-slot>

    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-hero
            :title="__('Valores dos planos')"
            :subtitle="__('Alterações refletem na landing page e no checkout de assinatura.')"
            icon="currency"
            iconTone="emerald"
        />

        @if (session('status'))
            <x-ui.success-alert :title="session('status')" />
        @endif

        <div class="space-y-6">
            @foreach ($plans as $plan)
                <form method="post" action="{{ route('admin.site.plans.update', $plan) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                    @csrf
                    @method('patch')

                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-4 dark:border-slate-700">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $plan->name }}</h2>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $plan->slug->value }}</span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="name_{{ $plan->id }}" :value="__('Nome exibido')" />
                            <input type="text" id="name_{{ $plan->id }}" name="name" value="{{ old('name', $plan->name) }}" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
                        </div>
                        <div>
                            <x-input-label for="monthly_{{ $plan->id }}" :value="__('Preço mensal (R$)')" />
                            <input type="number" step="0.01" min="0" id="monthly_{{ $plan->id }}" name="price_monthly" value="{{ old('price_monthly', number_format($plan->price_cents / 100, 2, '.', '')) }}" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
                        </div>
                        <div>
                            <x-input-label for="annual_{{ $plan->id }}" :value="__('Preço anual (R$)')" />
                            <input type="number" step="0.01" min="0" id="annual_{{ $plan->id }}" name="price_annual" value="{{ old('price_annual', number_format($plan->annual_price_cents / 100, 2, '.', '')) }}" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
                        </div>
                        <div>
                            <x-input-label for="max_{{ $plan->id }}" :value="__('Máx. pacientes (vazio = ilimitado)')" />
                            <input type="number" min="1" id="max_{{ $plan->id }}" name="max_patients" value="{{ old('max_patients', $plan->max_patients) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
                        </div>
                        <div>
                            <x-input-label for="sort_{{ $plan->id }}" :value="__('Ordem')" />
                            <input type="number" min="0" max="99" id="sort_{{ $plan->id }}" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm font-medium">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active)) class="rounded border-slate-300 text-violet-600" />
                                {{ __('Plano ativo no site') }}
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <x-primary-button>{{ __('Salvar plano') }}</x-primary-button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>
</x-app-layout>
