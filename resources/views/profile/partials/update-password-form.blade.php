@php
    $user = auth()->user();
    $hasPassword = $user->hasPassword();
    $socialAccounts = $user->socialAccounts()->get();
    $inputBase = 'block w-full rounded-xl border border-slate-200 bg-white py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';

    $fields = $hasPassword
        ? [
            'update_password_current_password' => ['name' => 'current_password', 'label' => __('Current Password'), 'model' => 'showCurrent', 'autocomplete' => 'current-password'],
            'update_password_password' => ['name' => 'password', 'label' => __('New Password'), 'model' => 'showNew', 'autocomplete' => 'new-password'],
            'update_password_password_confirmation' => ['name' => 'password_confirmation', 'label' => __('Confirm Password'), 'model' => 'showConfirm', 'autocomplete' => 'new-password'],
        ]
        : [
            'update_password_password' => ['name' => 'password', 'label' => __('Password'), 'model' => 'showNew', 'autocomplete' => 'new-password'],
            'update_password_password_confirmation' => ['name' => 'password_confirmation', 'label' => __('Confirm Password'), 'model' => 'showConfirm', 'autocomplete' => 'new-password'],
        ];
@endphp

<section
    class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60"
    x-data="{
        showCurrent: false,
        showNew: false,
        showConfirm: false,
    }"
>
    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </span>
        {{ $hasPassword ? __('Update Password') : __('Definir palavra-passe') }}
    </h3>

    @if (! $hasPassword)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            @if ($socialAccounts->isNotEmpty())
                {{ __('Entrou com uma conta social. Defina uma palavra-passe para também poder aceder com e-mail, recuperar a conta e confirmar ações sensíveis.') }}
            @else
                {{ __('Defina uma palavra-passe para aceder com e-mail e recuperar a conta se necessário.') }}
            @endif
        </p>

        @if ($socialAccounts->isNotEmpty())
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($socialAccounts as $account)
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ ucfirst($account->provider) }}
                    </span>
                @endforeach
            </div>
        @endif
    @else
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    @endif

    @if (session('status') === 'password-updated')
        <x-ui.success-alert
            class="mt-4"
            :title="__('Palavra-passe atualizada')"
            :message="__('A sua nova palavra-passe foi guardada com sucesso.')"
        />
    @endif

    @if (session('status') === 'password-set')
        <x-ui.success-alert
            class="mt-4"
            :title="__('Palavra-passe definida')"
            :message="__('Já pode entrar com e-mail e palavra-passe, além da conta social.')"
        />
    @endif

    <form method="post" action="{{ route('password.update') }}" class="mt-4 space-y-4">
        @csrf
        @method('put')

        @foreach ($fields as $id => $field)
            <div>
                <x-input-label :for="$id" :value="$field['label']" class="text-slate-700 dark:text-slate-200" />
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-violet-400 dark:text-violet-500" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                        </svg>
                    </span>
                    <input
                        id="{{ $id }}"
                        name="{{ $field['name'] }}"
                        x-bind:type="{{ $field['model'] }} ? 'text' : 'password'"
                        class="{{ $inputBase }} pl-10 pe-12"
                        autocomplete="{{ $field['autocomplete'] }}"
                        placeholder="••••••••"
                        required
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center rounded-r-xl pr-3.5 text-slate-400 transition hover:text-violet-600 focus:outline-none focus-visible:text-violet-600 dark:hover:text-violet-400"
                        @click="{{ $field['model'] }} = !{{ $field['model'] }}"
                        :aria-label="{{ $field['model'] }} ? '{{ __('Ocultar palavra-passe') }}' : '{{ __('Mostrar palavra-passe') }}'"
                    >
                        <svg x-show="!{{ $field['model'] }}" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="{{ $field['model'] }}" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get($field['name'])" class="mt-2" />
            </div>
        @endforeach

        <div class="flex justify-end border-t border-slate-100 pt-5 dark:border-slate-800">
            <x-primary-button class="justify-center sm:justify-start">
                {{ $hasPassword ? __('Save') : __('Definir palavra-passe') }}
            </x-primary-button>
        </div>
    </form>
</section>
