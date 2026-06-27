@php
    $user = auth()->user();
    $hasClinicalData = $user?->isProfessional() && $user->patients()->exists();
    $patientCount = $hasClinicalData ? $user->patients()->count() : 0;
    $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-rose-500';
    $checkboxCard = 'flex cursor-pointer items-start gap-3 rounded-xl border border-rose-200/80 bg-white/70 p-3.5 text-sm text-rose-950 shadow-sm transition hover:border-rose-300 dark:border-rose-900/50 dark:bg-slate-900/50 dark:text-rose-100 dark:hover:border-rose-700 has-[:checked]:border-rose-500/60 has-[:checked]:bg-rose-50/80 dark:has-[:checked]:bg-rose-950/40';
@endphp

<section class="rounded-2xl border border-rose-200/90 bg-gradient-to-br from-rose-50/80 via-white to-rose-50/40 p-5 shadow-sm ring-1 ring-rose-100 dark:border-rose-900/60 dark:from-rose-950/30 dark:via-slate-900/80 dark:to-rose-950/20 dark:ring-rose-900/40">
    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-rose-900 dark:text-rose-200">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-200/80 text-rose-900 dark:bg-rose-900/60 dark:text-rose-100" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </span>
        {{ __('Excluir conta') }}
    </h3>
    <p class="mt-1 text-xs text-rose-800/90 dark:text-rose-200/90">
        {{ __('Depois de excluir a conta, não poderá recuperá-la. Exporte dados que deseje conservar antes de continuar.') }}
    </p>

    @if ($hasClinicalData)
        <p class="mt-4 rounded-xl border border-amber-200/90 bg-amber-50/90 p-3.5 text-sm leading-relaxed text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100" role="alert">
            {{ __('Atenção: a sua conta profissional tem :count ficha(s) de paciente. A exclusão remove permanentemente pacientes, prontuários, sessões e demais registos clínicos associados (LGPD — obrigações legais de guarda continuam sendo sua responsabilidade).', ['count' => $patientCount]) }}
        </p>
    @endif

    <div class="mt-5">
        <x-danger-button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="rounded-xl px-5 py-2.5 shadow-sm"
        >{{ __('Excluir conta') }}</x-danger-button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable labelledby="confirm-user-deletion-title">
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6" x-data="{ showPassword: false }">
            @csrf
            @method('delete')

            <h2 id="confirm-user-deletion-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                {{ __('Tem certeza de que deseja excluir a sua conta?') }}
            </h2>

            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                {{ __('Introduza a palavra-passe para confirmar a eliminação permanente da conta.') }}
            </p>

            @if ($hasClinicalData)
                <label class="{{ $checkboxCard }} mt-4">
                    <input
                        type="checkbox"
                        name="acknowledge_data_loss"
                        value="1"
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-rose-300 text-rose-600 focus:ring-rose-500/30 dark:border-rose-700 dark:bg-slate-800"
                        @checked(old('acknowledge_data_loss'))
                    />
                    <span>{{ __('Compreendo que todos os dados clínicos de :count paciente(s) serão eliminados de forma irreversível.', ['count' => $patientCount]) }}</span>
                </label>
                <x-input-error :messages="$errors->userDeletion->get('acknowledge_data_loss')" class="mt-2" />
            @endif

            <div class="mt-5">
                <x-input-label for="password" :value="__('Password')" class="text-slate-700 dark:text-slate-200" />
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-rose-400 dark:text-rose-500" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </span>
                    <input
                        id="password"
                        name="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        class="{{ $inputBase }} pl-10 pe-12"
                        placeholder="{{ __('Password') }}"
                        autocomplete="current-password"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center rounded-r-xl pr-3.5 text-slate-400 transition hover:text-rose-600 focus:outline-none focus-visible:text-rose-600 dark:hover:text-rose-400"
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
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'confirm-user-deletion')" class="justify-center rounded-xl">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button class="justify-center rounded-xl">
                    {{ __('Excluir conta') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
