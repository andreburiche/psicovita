@php
    $activeCount = $partners->where('is_active', true)->count();
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Parcerias') }}</x-slot>

    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-hero
            :title="__('Confiança e parcerias')"
            :subtitle="__('Gerencie os parceiros exibidos na landing page. A logo é opcional — sem imagem, o nome em texto será mostrado.')"
            icon="building"
            iconTone="indigo"
        />

        @if (session('status'))
            <x-ui.success-alert :title="session('status')" />
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Total') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $partners->count() }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/20">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">{{ __('Ativos no site') }}</p>
                <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-300">{{ $activeCount }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Com logo') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $partners->filter(fn ($p) => filled($p->logoUrl()))->count() }}</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-dashed border-indigo-300/80 bg-gradient-to-br from-indigo-50/80 via-white to-sky-50/60 p-6 shadow-sm dark:border-indigo-800/60 dark:from-indigo-950/30 dark:via-slate-900/80 dark:to-slate-900/80">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Adicionar parceiro') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('PNG, JPG, WebP ou SVG até 2 MB.') }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                    <x-ui.icon name="plus" class="h-3.5 w-3.5" />
                    {{ __('Novo') }}
                </span>
            </div>

            <form method="post" action="{{ route('admin.site.partners.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-5 lg:grid-cols-12 lg:items-start">
                @csrf

                <div class="lg:col-span-3">
                    <x-input-label for="new_logo" :value="__('Logo (opcional)')" />
                    <label
                        for="new_logo"
                        class="mt-2 flex h-28 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-white/80 px-3 text-center transition hover:border-indigo-400 hover:bg-indigo-50/50 dark:border-slate-600 dark:bg-slate-900/60 dark:hover:border-indigo-600"
                    >
                        <x-ui.icon name="image" class="h-6 w-6 text-slate-400" />
                        <span class="mt-2 text-xs font-medium text-slate-500">{{ __('Clique para enviar') }}</span>
                    </label>
                    <input type="file" id="new_logo" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="sr-only" />
                    <x-input-error class="mt-1" :messages="$errors->get('logo')" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:col-span-7">
                    <div class="sm:col-span-2">
                        <x-input-label for="new_name" :value="__('Nome')" />
                        <input type="text" id="new_name" name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
                        <x-input-error class="mt-1" :messages="$errors->get('name')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="new_url" :value="__('URL do site (opcional)')" />
                        <input type="url" id="new_url" name="url" value="{{ old('url') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" placeholder="https://" />
                        <x-input-error class="mt-1" :messages="$errors->get('url')" />
                    </div>
                </div>

                <div class="flex items-end lg:col-span-2">
                    <x-primary-button class="w-full justify-center">{{ __('Adicionar') }}</x-primary-button>
                </div>
            </form>
        </section>

        @if ($partners->isNotEmpty())
            <section class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-900/40 sm:p-5">
                <h2 class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Pré-visualização no site') }}</h2>
                <div class="flex flex-wrap items-center justify-center gap-8 rounded-2xl border border-slate-200 bg-white px-6 py-8 opacity-80 grayscale dark:border-slate-700 dark:bg-slate-900/80">
                    @foreach ($partners->where('is_active', true) as $partner)
                        @include('admin.site.partials.partner-preview-item', ['partner' => $partner])
                    @endforeach
                </div>
            </section>
        @endif

        <div class="space-y-5">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Parceiros cadastrados') }}</h2>

            @forelse ($partners as $partner)
                @include('admin.site.partials.partner-card', ['partner' => $partner])
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-600 dark:bg-slate-900/60">
                    <x-ui.icon name="building" class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600" />
                    <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('Nenhum parceiro cadastrado.') }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Use o formulário acima para adicionar o primeiro.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
