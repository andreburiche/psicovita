@php
    $functionOptions = \App\Enums\UserProfessionalFunction::options();
@endphp

<x-auth-split-layout
    :heading="__('Criar conta')"
    :description="__('Crie a sua conta de profissional para começar a usar o PsiConecta.')"
    :promote-register="false"
    :promote-login="true"
>
    <x-social-auth-buttons />

    <form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="{ showPassword: false, showPasswordConfirm: false }">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Nome')" class="text-sm font-medium text-slate-700 dark:text-slate-300" />
            <x-text-input
                id="name"
                class="mt-2 block w-full rounded-xl border-slate-300 py-3 shadow-sm transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
            />
            <x-input-error :messages="$errors->get('name')" id="name-error" class="mt-2" />
        </div>

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
                <option value="" disabled @selected(! old('professional_function'))>{{ __('Selecione a sua função') }}</option>
                @foreach ($functionOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('professional_function') === $value)>{{ $label }}</option>
                @endforeach
                </select>
            </div>
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Ex.: psicólogo, psicoterapeuta, psiquiatra…') }}</p>
            <x-input-error :messages="$errors->get('professional_function')" id="professional_function-error" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('E-mail')" class="text-sm font-medium text-slate-700 dark:text-slate-300" />
            <x-text-input
                id="email"
                class="mt-2 block w-full rounded-xl border-slate-300 py-3 shadow-sm transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                type="email"
                name="email"
                :value="old('email')"
                required
                autocomplete="username"
                autocapitalize="none"
                spellcheck="false"
            />
            <x-input-error :messages="$errors->get('email')" id="email-error" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Senha')" class="text-sm font-medium text-slate-700 dark:text-slate-300" />
            <div class="relative mt-2">
                <x-text-input
                    id="password"
                    class="block w-full rounded-xl border-slate-300 py-3 pe-12 shadow-sm transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="new-password"
                />
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-300"
                    @click="showPassword = !showPassword"
                    :aria-label="showPassword ? '{{ __('Ocultar palavra-passe') }}' : '{{ __('Mostrar palavra-passe') }}'"
                >
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" id="password-error" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar senha')" class="text-sm font-medium text-slate-700 dark:text-slate-300" />
            <div class="relative mt-2">
                <x-text-input
                    id="password_confirmation"
                    class="block w-full rounded-xl border-slate-300 py-3 pe-12 shadow-sm transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                    x-bind:type="showPasswordConfirm ? 'text' : 'password'"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-300"
                    @click="showPasswordConfirm = !showPasswordConfirm"
                    :aria-label="showPasswordConfirm ? '{{ __('Ocultar confirmação') }}' : '{{ __('Mostrar confirmação') }}'"
                >
                    <svg x-show="!showPasswordConfirm" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="showPasswordConfirm" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" id="password-confirmation-error" class="mt-2" />
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/50">
            <label class="flex items-start gap-3 text-sm text-slate-700 dark:text-slate-300">
                <input
                    type="checkbox"
                    name="terms_accepted"
                    value="1"
                    class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                    @checked(old('terms_accepted'))
                    required
                    aria-describedby="terms-error"
                />
                <span>
                    {{ __('Li e aceito os') }}
                    <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="font-semibold text-violet-600 hover:underline dark:text-violet-400">{{ __('Termos de Uso') }}</a>
                    {{ __('e a') }}
                    <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="font-semibold text-violet-600 hover:underline dark:text-violet-400">{{ __('Política de Privacidade') }}</a>.
                </span>
            </label>
            <x-input-error :messages="$errors->get('terms_accepted')" id="terms-error" class="mt-2" />
        </div>

        <button
            type="submit"
            class="flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
        >
            {{ __('Criar conta') }}
        </button>
    </form>
</x-auth-split-layout>
