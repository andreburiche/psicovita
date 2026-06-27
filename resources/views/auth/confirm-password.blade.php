@php
    $user = auth()->user();
    $inputBase = 'block w-full rounded-xl border border-slate-200 bg-white py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
@endphp

<x-auth-split-layout
    :heading="__('Confirme a sua palavra-passe')"
    :description="__('Por segurança, confirme a identidade antes de continuar nesta área sensível.')"
    :promote-register="false"
    :promote-login="false"
>
    <div class="space-y-6" data-test="confirm-password-page">
        <section class="overflow-hidden rounded-2xl border border-amber-200/80 bg-gradient-to-br from-amber-50/80 via-white to-orange-50/50 p-5 shadow-sm ring-1 ring-amber-100 dark:border-amber-900/40 dark:from-amber-950/30 dark:via-slate-900/80 dark:to-orange-950/20 dark:ring-amber-900/30">
            <div class="flex items-start gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/25" aria-hidden="true">
                    <x-ui.icon name="shield" class="h-7 w-7" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300">{{ __('Área protegida') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
                    <p class="mt-0.5 break-all text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ __('Esta confirmação é temporária e ajuda a proteger dados clínicos e configurações da sua conta.') }}
                    </p>
                </div>
            </div>
        </section>

        <form
            method="POST"
            action="{{ route('password.confirm') }}"
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

            <section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400" aria-hidden="true">
                        <x-ui.icon name="lock" class="h-4 w-4" />
                    </span>
                    {{ __('Palavra-passe atual') }}
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Introduza a palavra-passe da conta com que está autenticado.') }}</p>

                <div class="relative mt-4">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-violet-400 dark:text-violet-500" aria-hidden="true">
                        <x-ui.icon name="lock" class="h-4 w-4" />
                    </span>
                    <input
                        id="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        name="password"
                        class="{{ $inputBase }} pl-10 pe-12"
                        required
                        autofocus
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center rounded-r-xl pr-3.5 text-slate-400 transition hover:text-violet-600 focus:outline-none focus-visible:text-violet-600 dark:hover:text-violet-400"
                        @click="showPassword = !showPassword"
                        :aria-label="showPassword ? @js(__('Ocultar palavra-passe')) : @js(__('Mostrar palavra-passe'))"
                    >
                        <x-ui.icon name="eye" class="h-5 w-5" x-show="!showPassword" />
                        <x-ui.icon name="eye-slash" class="h-5 w-5" x-show="showPassword" x-cloak />
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </section>

            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
            >
                <x-ui.icon name="check" class="h-4 w-4" />
                {{ __('Confirmar e continuar') }}
            </button>
        </form>

        <p class="text-center text-xs leading-relaxed text-slate-500 dark:text-slate-400">
            {{ __('A sessão de confirmação expira após alguns minutos de inatividade, por medida de segurança.') }}
        </p>
    </div>
</x-auth-split-layout>
