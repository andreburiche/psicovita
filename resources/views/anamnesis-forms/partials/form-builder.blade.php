@props([
    'submitAction',
    'httpMethod' => 'post',
    'record' => null,
    'initialQuestions' => [],
    'fieldDefaultsJson' => [],
    'submitLabel',
])

@php
    $types = \App\Support\FieldTypeDefaults::TYPES;
@endphp

<form action="{{ $submitAction }}" method="post" class="space-y-8">
    @csrf
    @if (strtolower($httpMethod) === 'put')
        @method('PUT')
    @endif

    <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/60">
        <div>
            <x-input-label for="title" :value="__('Título do modelo')" />
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $record?->title)" required />
            <x-input-error class="mt-2" :messages="$errors->get('title')" />
        </div>
        <div>
            <x-input-label for="description" :value="__('Descrição (opcional)')" />
            <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('description', $record?->description) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>
    </div>

    <div
        class="rounded-2xl border border-slate-200 bg-gradient-to-br from-white to-violet-50/40 p-6 shadow-sm dark:border-slate-700 dark:from-slate-900 dark:to-violet-950/30"
        x-data="anamnesisBuilder(@js($initialQuestions), @js($fieldDefaultsJson))"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Campos da anamnese') }}</h3>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('Ao mudar o tipo, máscara e validações padrão são aplicadas automaticamente.') }}</p>
            </div>
            <button type="button" @click="addRow()" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-violet-700">
                {{ __('Adicionar campo') }}
            </button>
        </div>

        <div class="mt-6 space-y-6">
            <template x-for="(q, i) in questions" :key="i">
                <div class="rounded-xl border border-slate-200 bg-white/90 p-4 dark:border-slate-600 dark:bg-slate-900/80">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                        <div class="lg:col-span-4">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300" :for="'lbl-'+i">{{ __('Rótulo') }}</label>
                            <input :id="'lbl-'+i" type="text" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" x-model="q.label" :name="`questions[${i}][label]`" required />
                        </div>
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300" :for="'key-'+i">{{ __('Chave (slug)') }}</label>
                            <input :id="'key-'+i" type="text" class="mt-1 block w-full rounded-xl border-slate-300 font-mono text-sm dark:border-slate-600 dark:bg-slate-800" x-model="q.field_key" :name="`questions[${i}][field_key]`" pattern="[a-z][a-z0-9_]*" required />
                        </div>
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300" :for="'type-'+i">{{ __('Tipo') }}</label>
                            <select :id="'type-'+i" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" x-model="q.field_type" @change="onTypeChange(i)" :name="`questions[${i}][field_type]`">
                                @foreach ($types as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2 lg:col-span-2">
                            <input type="hidden" :name="`questions[${i}][required]`" :value="q.required ? '1' : '0'" />
                            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="checkbox" x-model="q.required" class="rounded border-slate-300 text-violet-600" />
                                {{ __('Obrigatório') }}
                            </label>
                            <button
                                type="button"
                                class="ml-auto inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 shadow-sm transition hover:bg-rose-100 hover:text-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-500/40 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-950/70"
                                @click="removeRow(i)"
                                title="{{ __('Remover campo') }}"
                                aria-label="{{ __('Remover campo') }}"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.038-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                <span class="sr-only">{{ __('Remover campo') }}</span>
                            </button>
                        </div>
                    </div>

                    <input type="hidden" :name="`questions[${i}][sort_order]`" :value="i" />

                    <input type="hidden" :name="`questions[${i}][mask]`" :value="q.mask ?? ''" />

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Máscara (identificador)') }}</label>
                            <input type="text" class="mt-1 block w-full rounded-lg border-dashed border-slate-300 bg-slate-50 font-mono text-xs dark:border-slate-600 dark:bg-slate-950" x-model="q.mask" readonly tabindex="-1" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Regras (somente leitura)') }}</label>
                            <input type="text" class="mt-1 block w-full rounded-lg border-dashed border-slate-300 bg-slate-50 font-mono text-xs dark:border-slate-600 dark:bg-slate-950" :value="(q.validation_rules || []).join(', ')" readonly tabindex="-1" />
                        </div>
                    </div>

                    <template x-for="(rule, ri) in (q.validation_rules || [])" :key="ri">
                        <input type="hidden" :name="`questions[${i}][validation_rules][${ri}]`" :value="rule" />
                    </template>
                </div>
            </template>
        </div>

        <x-input-error class="mt-4" :messages="$errors->get('questions')" />
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('anamnesis-forms.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400">{{ __('Cancelar') }}</a>
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
    </div>
</form>
