@if ($user->isProfessional() && config('asaas.split_enabled'))
    @php
        $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
        $connectEnabled = (bool) config('asaas.connect_enabled');
        $hasWallet = filled($user->asaas_wallet_id);
    @endphp

    <section class="rounded-2xl border border-emerald-200/80 bg-white p-6 shadow-lg shadow-emerald-900/5 ring-1 ring-emerald-100/60 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-emerald-900/30">
        <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Recebimentos via Asaas') }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ __('Carteira para repasse automático dos pagamentos clínicos dos seus pacientes (split).') }}
            </p>
        </div>

        @error('asaas_wallet')
            <x-flash-alert type="error" :message="$message" class="mt-4" />
        @enderror

        @if (! $hasWallet)
            @if ($connectEnabled && blank($user->phone))
                <div class="mt-4 rounded-xl border border-amber-200/80 bg-amber-50/80 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100" role="status">
                    {{ __('Antes de criar a carteira, preencha o telefone na secção «Profile Information» acima.') }}
                </div>
            @endif

            <form method="post" action="{{ route('profile.asaas-wallet.provision') }}" class="mt-5 space-y-4 rounded-xl border border-dashed border-emerald-300/80 bg-emerald-50/50 p-4 dark:border-emerald-800/50 dark:bg-emerald-950/20">
                @csrf
                @if ($connectEnabled)
                    <div
                        class="grid gap-4 sm:grid-cols-2"
                        data-cep-wrap
                        data-cep-targets='@json([
                            "street" => "#provision_address",
                            "district" => "#provision_province",
                        ])'
                    >
                        <div class="sm:col-span-2">
                            <x-input-label for="provision_cpf_cnpj" :value="__('CPF ou CNPJ')" class="text-slate-700 dark:text-slate-200" />
                            <input id="provision_cpf_cnpj" name="cpf_cnpj" type="text" class="{{ $inputBase }}" value="{{ old('cpf_cnpj') }}" required />
                        </div>
                        <div>
                            <x-input-label for="provision_postal_code" :value="__('CEP')" class="text-slate-700 dark:text-slate-200" />
                            <input id="provision_postal_code" name="postal_code" type="text" inputmode="numeric" autocomplete="postal-code" data-cep-lookup class="{{ $inputBase }}" value="{{ old('postal_code') }}" placeholder="00000-000" required />
                        </div>
                        <div>
                            <x-input-label for="provision_address_number" :value="__('Número')" class="text-slate-700 dark:text-slate-200" />
                            <input id="provision_address_number" name="address_number" type="text" class="{{ $inputBase }}" value="{{ old('address_number') }}" required />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="provision_address" :value="__('Morada')" class="text-slate-700 dark:text-slate-200" />
                            <input id="provision_address" name="address" type="text" class="{{ $inputBase }}" value="{{ old('address') }}" required />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="provision_province" :value="__('Bairro')" class="text-slate-700 dark:text-slate-200" />
                            <input id="provision_province" name="province" type="text" class="{{ $inputBase }}" value="{{ old('province') }}" required />
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Dados exigidos pelo Asaas Connect para abrir a subconta de recebimento. Ao sair do CEP válido, morada e bairro são preenchidos automaticamente.') }}</p>
                @endif
                <button
                    type="submit"
                    class="inline-flex items-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-violet-500 hover:to-indigo-500"
                >
                    {{ $connectEnabled ? __('Criar carteira no Asaas') : __('Gerar carteira (modo demonstração)') }}
                </button>
            </form>
        @else
            <p class="mt-4 rounded-xl bg-emerald-50/80 px-4 py-3 text-sm font-medium text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ __('Carteira activa') }}: <span class="font-mono">{{ $user->asaas_wallet_id }}</span>
            </p>
        @endif

        <form method="post" action="{{ route('profile.update') }}" class="mt-5 space-y-4">
            @csrf
            @method('patch')

            <input type="hidden" name="name" value="{{ $user->name }}">
            <input type="hidden" name="email" value="{{ $user->email }}">
            @if ($user->crp_number)
                <input type="hidden" name="crp_number" value="{{ $user->crp_number }}">
            @endif
            @if ($user->professional_function)
                <input type="hidden" name="professional_function" value="{{ $user->professional_function->value }}">
            @endif

            <div>
                <x-input-label for="asaas_wallet_id" :value="__('ID da carteira Asaas')" class="text-slate-700 dark:text-slate-200" />
                <input
                    id="asaas_wallet_id"
                    name="asaas_wallet_id"
                    type="text"
                    class="{{ $inputBase }} font-mono"
                    value="{{ old('asaas_wallet_id', $user->asaas_wallet_id) }}"
                    placeholder="wal_xxxxxxxxxxxxxxxx"
                    autocomplete="off"
                    spellcheck="false"
                />
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    {{ __('Pode criar automaticamente acima ou colar o ID manualmente do painel Asaas.') }}
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('asaas_wallet_id')" />
            </div>

            <button
                type="submit"
                class="inline-flex items-center rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-emerald-500 hover:to-teal-500"
            >
                {{ __('Guardar carteira') }}
            </button>
        </form>
    </section>
@endif
