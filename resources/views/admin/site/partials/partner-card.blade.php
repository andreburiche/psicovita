<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
    <form method="post" action="{{ route('admin.site.partners.update', $partner) }}" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/40">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-sm font-bold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                    {{ $partner->sort_order }}
                </span>
                <div class="min-w-0">
                    <h3 class="truncate text-base font-bold text-slate-900 dark:text-white">{{ $partner->name }}</h3>
                    <p class="truncate text-xs text-slate-500">
                        @if (filled($partner->url))
                            {{ $partner->url }}
                        @else
                            {{ __('Sem link externo') }}
                        @endif
                    </p>
                </div>
            </div>

            <span @class([
                'inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold',
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' => $partner->is_active,
                'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => ! $partner->is_active,
            ])>
                {{ $partner->is_active ? __('Ativo') : __('Inativo') }}
            </span>
        </header>

        <div class="grid gap-5 p-5 lg:grid-cols-12 lg:items-start">
            <div class="lg:col-span-3">
                <x-input-label :for="'logo_'.$partner->id" :value="__('Logo')" />
                <div class="mt-2 flex h-32 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-600 dark:bg-slate-800/50">
                    @if ($partner->logoUrl())
                        <img
                            src="{{ $partner->logoUrl() }}"
                            alt="{{ $partner->name }}"
                            class="max-h-full max-w-full object-contain"
                        />
                    @else
                        <div class="text-center">
                            <x-ui.icon name="image" class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-600" />
                            <p class="mt-1 text-[11px] text-slate-400">{{ __('Sem logo') }}</p>
                        </div>
                    @endif
                </div>

                <input
                    type="file"
                    id="logo_{{ $partner->id }}"
                    name="logo"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    class="mt-2 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-400 dark:file:bg-indigo-900/50 dark:file:text-indigo-300"
                />

                @if ($partner->logoUrl())
                    <label class="mt-2 inline-flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-400">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-red-600" />
                        {{ __('Remover logo') }}
                    </label>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-6">
                <div class="sm:col-span-2">
                    <x-input-label :for="'name_'.$partner->id" :value="__('Nome')" />
                    <input type="text" id="name_{{ $partner->id }}" name="name" value="{{ old('name', $partner->name) }}" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label :for="'url_'.$partner->id" :value="__('URL do site')" />
                    <input type="url" id="url_{{ $partner->id }}" name="url" value="{{ old('url', $partner->url) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" placeholder="https://" />
                </div>
                <div>
                    <x-input-label :for="'sort_'.$partner->id" :value="__('Ordem')" />
                    <input type="number" min="0" max="99" id="sort_{{ $partner->id }}" name="sort_order" value="{{ old('sort_order', $partner->sort_order) }}" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="is_active" value="1" @checked($partner->is_active) class="rounded border-slate-300 text-indigo-600" />
                        {{ __('Exibir no site') }}
                    </label>
                </div>
            </div>

            <div class="flex flex-col justify-end gap-2 lg:col-span-3">
                <x-primary-button class="w-full justify-center">{{ __('Salvar alterações') }}</x-primary-button>
            </div>
        </div>
    </form>

    @php
        $removePartnerDetails = [
            ['label' => __('Parceiro'), 'value' => $partner->name],
        ];

        if ($partner->logoUrl()) {
            $removePartnerDetails[] = ['label' => __('Logo'), 'value' => __('Será apagada do servidor')];
        }

        if (filled($partner->url)) {
            $removePartnerDetails[] = ['label' => __('Site'), 'value' => $partner->url];
        }
    @endphp

    <footer class="flex justify-end border-t border-slate-100 px-5 py-3 dark:border-slate-700">
        <x-confirm-form
            method="post"
            action="{{ route('admin.site.partners.destroy', $partner) }}"
            class="inline-flex"
            :title="__('Remover parceiro?')"
            :message="__('O parceiro deixará de aparecer na secção «Confiança e parcerias» da landing page.')"
            :hint="__('Esta ação não pode ser desfeita. Se existir logo, o ficheiro também será eliminado.')"
            :eyebrow="__('Ação irreversível')"
            :confirm-label="__('Sim, remover parceiro')"
            :cancel-label="__('Manter parceiro')"
            variant="danger"
            :validate="false"
            :details="$removePartnerDetails"
        >
            @csrf
            @method('delete')
            <button
                type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/30 dark:hover:text-red-300"
            >
                <x-ui.icon name="trash" class="h-3.5 w-3.5" />
                {{ __('Remover parceiro') }}
            </button>
        </x-confirm-form>
    </footer>
</article>
