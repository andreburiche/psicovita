@php
    $inputBase = 'block w-full rounded-xl border border-slate-200 bg-white py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
    $checkboxCard = 'flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200/90 bg-slate-50/60 px-3.5 py-3 text-sm text-slate-600 shadow-sm transition hover:border-violet-300/70 hover:bg-violet-50/40 has-[:checked]:border-violet-500/60 has-[:checked]:bg-violet-50/70 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-300 dark:hover:border-violet-500/40 dark:hover:bg-violet-950/30 dark:has-[:checked]:border-violet-500/50 dark:has-[:checked]:bg-violet-950/40';
@endphp

<x-auth-split-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form
        method="POST"
        action="{{ route('login') }}"
        class="space-y-6"
        x-data="{ showPassword: false }"
    >
        @csrf

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-social-auth-buttons />

        <section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
            <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                    </svg>
                </span>
                {{ __('Credenciais de acesso') }}
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Utilize o e-mail registado na sua conta e a respetiva palavra-passe.') }}</p>

            <div class="mt-4 space-y-4">
                <div>
                    <x-input-label for="email" :value="__('Email')" class="text-slate-700 dark:text-slate-200" />
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-violet-400 dark:text-violet-500" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="{{ $inputBase }} pl-10"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nome@exemplo.com"
                            autocapitalize="none"
                            spellcheck="false"
                        />
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <div class="flex items-center justify-between gap-2">
                        <x-input-label for="password" :value="__('Password')" class="text-slate-700 dark:text-slate-200" />
                        @if (Route::has('password.request'))
                            <a class="text-xs font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400 dark:hover:text-violet-300" href="{{ route('password.request') }}">
                                {{ __('Forgot your password?') }}
                            </a>
                        @endif
                    </div>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-violet-400 dark:text-violet-500" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </span>
                        <input
                            id="password"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            name="password"
                            class="{{ $inputBase }} pl-10 pe-12"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center rounded-r-xl pr-3.5 text-slate-400 transition hover:text-violet-600 focus:outline-none focus-visible:text-violet-600 dark:hover:text-violet-400"
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
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>
        </section>

        <label for="remember_me" class="{{ $checkboxCard }}">
            <input
                id="remember_me"
                type="checkbox"
                class="h-4 w-4 shrink-0 rounded border-slate-300 text-violet-600 focus:ring-violet-500/30 dark:border-slate-500 dark:bg-slate-800"
                name="remember"
            />
            <span>{{ __('Remember me') }}</span>
        </label>

        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
            </svg>
            {{ __('Log in') }}
        </button>
    </form>
</x-auth-split-layout>
