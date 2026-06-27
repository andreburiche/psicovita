<x-guest-layout>
    <div class="mx-auto max-w-lg space-y-6">
        <div class="rounded-2xl border border-violet-200/80 bg-white p-6 shadow-lg dark:border-slate-700 dark:bg-slate-900/90">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('Activar acesso ao portal') }}</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                {{ __(':professional convidou-o(a) para aceder ao portal do paciente no :app.', [
                    'professional' => $professional?->name ?? __('O seu profissional'),
                    'app' => config('app.name'),
                ]) }}
            </p>
            @if ($patient)
                <p class="mt-1 text-xs text-slate-500">{{ __('Paciente') }}: {{ $patient->name }}</p>
            @endif

            <form method="POST" action="{{ route('patient-portal.activate.store', $invitation->token) }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="password" :value="__('Palavra-passe')" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirmar palavra-passe')" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                </div>

                <label class="flex items-start gap-3 text-sm text-slate-600 dark:text-slate-300">
                    <input type="checkbox" name="terms_accepted" value="1" class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500" @checked(old('terms_accepted')) required />
                    <span>
                        {!! __('Aceito os :terms e a :privacy.', [
                            'terms' => '<a href="'.route('legal.terms').'" target="_blank" class="font-semibold text-violet-600 hover:underline">'.__('Termos de Uso').'</a>',
                            'privacy' => '<a href="'.route('legal.privacy').'" target="_blank" class="font-semibold text-violet-600 hover:underline">'.__('Política de Privacidade').'</a>',
                        ]) !!}
                    </span>
                </label>
                <x-input-error class="mt-2" :messages="$errors->get('terms_accepted')" />

                <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-md">
                    {{ __('Activar e entrar') }}
                </button>
            </form>

            <p class="mt-4 text-center text-xs text-slate-500">
                {{ __('O convite expira em :date.', ['date' => $invitation->expires_at->format('d/m/Y H:i')]) }}
            </p>
        </div>
    </div>
</x-guest-layout>
