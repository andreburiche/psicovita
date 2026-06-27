@php
    $inputBase = 'block w-full rounded-xl border border-slate-200 bg-white py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
@endphp

<x-auth-split-layout
    :heading="__('Forgot your password?')"
    :description="__('Informe o e-mail da sua conta para receber um link seguro de redefinição.')"
    :promote-register="false"
    :promote-login="true"
>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
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

        <div class="rounded-xl border border-sky-200/90 bg-gradient-to-br from-sky-50/90 via-white to-indigo-50/40 p-4 text-sm leading-relaxed text-slate-600 shadow-sm ring-1 ring-sky-100 dark:border-sky-900/50 dark:from-sky-950/30 dark:via-slate-900/80 dark:to-indigo-950/20 dark:text-slate-300 dark:ring-sky-900/30">
            <p class="flex items-start gap-2.5">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-sky-100 text-sky-700 dark:bg-sky-900/60 dark:text-sky-300" aria-hidden="true">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </span>
                {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
            </p>
        </div>

        <section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
            <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                </span>
                {{ __('E-mail da conta') }}
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Enviaremos o link apenas se existir uma conta associada a este endereço.') }}</p>

            <div class="mt-4">
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
                        class="{{ $inputBase }} pl-10"
                        value="{{ old('email') }}"
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
        </section>

        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75M3.75 15.75h-.008v-.008h.008v.008zm0 3h.008v-.008h-.008v.008zm0-3h.008v-.008h-.008v.008zm0-3h.008v-.008h-.008v.008zm3-6h.008v-.008h-.008v.008zm0 3h.008v-.008h-.008v.008zm0 3h.008v-.008h-.008v.008zm3-3h.008v-.008h-.008v.008zm0 3h.008v-.008h-.008v.008zm0 3h.008v-.008h-.008v.008zm3-3h.008v-.008h-.008v.008zm0 3h.008v-.008h-.008v.008z" />
            </svg>
            {{ __('Email Password Reset Link') }}
        </button>
    </form>
</x-auth-split-layout>
