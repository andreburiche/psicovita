<x-guest-layout>
    <div class="mx-auto max-w-lg space-y-6">
        <div class="rounded-2xl border border-indigo-200/80 bg-white p-6 shadow-lg dark:border-slate-700 dark:bg-slate-900/90">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('Convite para equipa') }}</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                {{ __(':owner convidou-o(a) para colaborar na clínica no :app.', ['owner' => $owner->name, 'app' => config('app.name')]) }}
            </p>
            <p class="mt-1 text-xs text-slate-500">{{ __('E-mail do convite') }}: {{ $invitation->email }}</p>

            @error('clinic_team')
                <x-flash-alert type="error" :message="$message" class="mt-4" />
            @enderror

            @auth
                @if (auth()->user()->normalizedEmail() === $invitation->email)
                    <form method="POST" action="{{ route('clinic.invitations.accept', $invitation->token) }}" class="mt-6">
                        @csrf
                        <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-md">
                            {{ __('Aceitar e entrar na equipa') }}
                        </button>
                    </form>
                @else
                    <p class="mt-4 text-sm text-amber-800 dark:text-amber-200">
                        {{ __('Inicie sessão com :email para aceitar.', ['email' => $invitation->email]) }}
                    </p>
                @endif
            @else
                <div class="mt-6 flex flex-col gap-3">
                    <a href="{{ route('login') }}" class="inline-flex justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 dark:border-slate-600 dark:text-slate-200">
                        {{ __('Iniciar sessão') }}
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-md">
                        {{ __('Criar conta profissional') }}
                    </a>
                </div>
            @endauth
        </div>
    </div>
</x-guest-layout>
