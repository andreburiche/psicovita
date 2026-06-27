@php
    $inputBase = 'block w-full rounded-xl border border-slate-200 bg-white py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
@endphp

<x-auth-split-layout
    :heading="__('Redefinir palavra-passe')"
    :description="__('Escolha uma nova palavra-passe segura para a sua conta.')"
    :promote-register="false"
    :promote-login="true"
>
    <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-slate-700 dark:text-slate-200" />
            <input
                id="email"
                type="email"
                name="email"
                class="{{ $inputBase }} mt-1.5"
                value="{{ old('email', $request->email) }}"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Nova palavra-passe')" class="text-slate-700 dark:text-slate-200" />
            <input
                id="password"
                type="password"
                name="password"
                class="{{ $inputBase }} mt-1.5"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar palavra-passe')" class="text-slate-700 dark:text-slate-200" />
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                class="{{ $inputBase }} mt-1.5"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
        >
            {{ __('Redefinir palavra-passe') }}
        </button>
    </form>
</x-auth-split-layout>
