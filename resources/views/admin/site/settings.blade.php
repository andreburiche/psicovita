<x-app-layout>
    <x-slot name="header">{{ __('Site público') }}</x-slot>

    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-hero
            :title="__('Redes sociais e WhatsApp')"
            :subtitle="__('Links exibidos na landing page — barra lateral, botão flutuante e contacto.')"
            icon="globe"
            iconTone="sky"
        />

        @if (session('status'))
            <x-ui.success-alert :title="session('status')" />
        @endif

        <form method="post" action="{{ route('admin.site.settings.update') }}" class="space-y-6">
            @csrf
            @method('patch')

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Redes sociais') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('Deixe em branco para ocultar o ícone na barra fixa do site.') }}</p>
                <div class="mt-4 space-y-4">
                    @foreach ([
                        'instagram' => 'Instagram',
                        'linkedin' => 'LinkedIn',
                        'facebook' => 'Facebook',
                        'youtube' => 'YouTube',
                    ] as $key => $label)
                        <div>
                            <x-input-label :for="$key" :value="$label" />
                            <input
                                type="url"
                                id="{{ $key }}"
                                name="{{ $key }}"
                                value="{{ old($key, $social[$key] ?? '') }}"
                                placeholder="https://"
                                class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900"
                            />
                            <x-input-error class="mt-1" :messages="$errors->get($key)" />
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-emerald-200/80 bg-white p-6 shadow-sm dark:border-emerald-900/40 dark:bg-slate-900/80">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('WhatsApp de contacto') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('Número com DDD (ex.: 11999990000). O indicativo do país é aplicado automaticamente se configurado.') }}</p>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                            <input type="checkbox" name="whatsapp_enabled" value="1" @checked(old('whatsapp_enabled', $whatsapp['enabled'] ?? true)) class="rounded border-slate-300 text-emerald-600" />
                            {{ __('Exibir botão flutuante no site') }}
                        </label>
                    </div>
                    <div>
                        <x-input-label for="whatsapp_phone" :value="__('Telefone')" />
                        <input type="text" id="whatsapp_phone" name="whatsapp_phone" value="{{ old('whatsapp_phone', $whatsapp['phone'] ?? '') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" placeholder="11999990000" />
                        <x-input-error class="mt-1" :messages="$errors->get('whatsapp_phone')" />
                    </div>
                    <div>
                        <x-input-label for="whatsapp_message" :value="__('Mensagem pré-preenchida')" />
                        <textarea id="whatsapp_message" name="whatsapp_message" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900">{{ old('whatsapp_message', $whatsapp['message'] ?? '') }}</textarea>
                    </div>
                </div>
            </section>

            <x-primary-button>{{ __('Salvar configurações') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
