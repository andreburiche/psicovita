@php
    $becomesPatient = (bool) ($payload['becomes_patient'] ?? false);
    $providerLabel = ucfirst((string) ($payload['provider'] ?? ''));
@endphp

<x-auth-split-layout
    :heading="__('Concluir registo')"
    :description="__('Complete os dados da sua conta :provider.', ['provider' => $providerLabel])"
    :promote-register="false"
    :promote-login="true"
>
    <form method="POST" action="{{ route('social.register.store') }}" class="space-y-5">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300">
            <p class="font-medium text-slate-800 dark:text-slate-100">{{ __('Conta :provider', ['provider' => $providerLabel]) }}</p>
            <p class="mt-1">{{ $payload['email'] }}</p>
        </div>

        <div>
            <x-input-label for="name" :value="__('Nome')" class="text-sm font-medium text-slate-700 dark:text-slate-300" />
            <x-text-input
                id="name"
                class="mt-2 block w-full rounded-xl border-slate-300 py-3 shadow-sm transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                type="text"
                name="name"
                :value="old('name', $payload['name'] ?? '')"
                required
                autofocus
                autocomplete="name"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        @if ($becomesPatient)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-100">
                {{ __('O seu e-mail está associado a um consultório. A conta será criada como paciente.') }}
            </div>
        @else
            <div>
                <x-input-label for="professional_function" :value="__('Função')" class="text-sm font-medium text-slate-700 dark:text-slate-300" />
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-violet-500" aria-hidden="true">
                        <x-ui.icon name="briefcase" class="h-4 w-4" />
                    </span>
                    <select
                        id="professional_function"
                        name="professional_function"
                        class="block w-full rounded-xl border-slate-300 bg-white py-3 pl-10 pr-3 text-sm shadow-sm transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                        required
                    >
                        <option value="">{{ __('Selecione…') }}</option>
                        @foreach ($functionOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('professional_function') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input-error :messages="$errors->get('professional_function')" class="mt-2" />
            </div>
        @endif

        <label for="terms_accepted" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200/90 bg-slate-50/60 px-3.5 py-3 text-sm text-slate-600 shadow-sm dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-300">
            <input
                id="terms_accepted"
                type="checkbox"
                name="terms_accepted"
                value="1"
                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-violet-600 focus:ring-violet-500/30 dark:border-slate-500 dark:bg-slate-800"
                @checked(old('terms_accepted'))
                required
            />
            <span>
                {!! __('Li e aceito os :terms e a :privacy.', [
                    'terms' => '<a href="'.route('terms').'" class="font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400" target="_blank" rel="noopener">'.__('Termos de Uso').'</a>',
                    'privacy' => '<a href="'.route('privacy').'" class="font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400" target="_blank" rel="noopener">'.__('Política de Privacidade').'</a>',
                ]) !!}
            </span>
        </label>
        <x-input-error :messages="$errors->get('terms_accepted')" class="mt-2" />

        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500"
        >
            {{ __('Criar conta') }}
        </button>
    </form>
</x-auth-split-layout>
