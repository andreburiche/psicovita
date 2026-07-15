@php
    $paymentSettings = app(\App\Services\PaymentSettingsService::class);
    $isTeamMember = $user->isClinicTeamMember();
    $resolution = $isTeamMember
        ? $paymentSettings->resolvePaymentMethodFor($user)
        : $paymentSettings->resolvePaymentMethodFor($user);
    $practiceOwner = $paymentSettings->practiceOwnerFor($user);
@endphp

@if ($user->isProfessional() && $isTeamMember)
    <section class="rounded-2xl border border-slate-200/90 bg-slate-50/80 p-5 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Recebimento') }}</h2>
        <p class="mt-2">
            {{ __('O recebimento de pagamentos é gerenciado por :name.', ['name' => $practiceOwner->name]) }}
        </p>
    </section>
@elseif ($user->isProfessional() && $user->isClinicOwner())
    @php
        $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
        $connectEnabled = (bool) config('asaas.connect_enabled');
        $hasWallet = filled($user->asaas_wallet_id);
        $splitEnabled = (bool) config('asaas.split_enabled');
    @endphp

    <section
        class="rounded-2xl border border-emerald-200/80 bg-white p-6 shadow-lg shadow-emerald-900/5 ring-1 ring-emerald-100/60 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-emerald-900/30"
        x-data="paymentSettingsForm({
            preference: @js(old('payment_method_preference', $user->payment_method_preference?->value ?? 'auto')),
            link: @js(old('pix_manual_link', $user->pix_manual_link)),
            qrUrl: @js($user->pixQrcodeUrl()),
            badge: @js($resolution->statusBadgeLabel()),
            mode: @js($resolution->mode),
            saveUrl: @js(route('profile.payment-settings.update')),
            csrf: @js(csrf_token()),
        })"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Recebimento') }}</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    {{ __('Defina como os pacientes pagam as consultas: Asaas automático ou PIX manual.') }}
                </p>
            </div>
            <span
                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1"
                :class="{
                    'bg-emerald-100 text-emerald-900 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-100': mode === 'asaas',
                    'bg-sky-100 text-sky-900 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-100': mode === 'manual',
                    'bg-amber-100 text-amber-950 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-100': mode === 'not_configured',
                }"
                x-text="badge"
            ></span>
        </div>

        @if ($splitEnabled)
            <div class="mt-6 rounded-xl border border-emerald-200/70 bg-emerald-50/40 p-4 dark:border-emerald-800/40 dark:bg-emerald-950/20">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Status Asaas') }}</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    {{ __('Carteira para cobranças automáticas e split dos pagamentos clínicos.') }}
                </p>

                @error('asaas_wallet')
                    <x-flash-alert type="error" :message="$message" class="mt-4" />
                @enderror

                @if (! $hasWallet)
                    @if ($connectEnabled && blank($user->phone))
                        <div class="mt-4 rounded-xl border border-amber-200/80 bg-amber-50/80 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100" role="status">
                            {{ __('Antes de criar a carteira, preencha o telefone na secção «Profile Information» acima.') }}
                        </div>
                    @endif

                    <form method="post" action="{{ route('profile.asaas-wallet.provision') }}" class="mt-4 space-y-4">
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
                        @endif
                        <button type="submit" class="inline-flex items-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-violet-500 hover:to-indigo-500">
                            {{ $connectEnabled ? __('Criar carteira no Asaas') : __('Gerar carteira (modo demonstração)') }}
                        </button>
                    </form>
                @else
                    <p class="mt-4 rounded-xl bg-emerald-50/80 px-4 py-3 text-sm font-medium text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                        {{ __('Carteira activa') }}: <span class="font-mono">{{ $user->asaas_wallet_id }}</span>
                    </p>
                @endif

                <form method="post" action="{{ route('profile.update') }}" class="mt-4 space-y-3">
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
                        <input id="asaas_wallet_id" name="asaas_wallet_id" type="text" class="{{ $inputBase }} font-mono" value="{{ old('asaas_wallet_id', $user->asaas_wallet_id) }}" placeholder="wal_xxxxxxxxxxxxxxxx" autocomplete="off" spellcheck="false" />
                        <x-input-error class="mt-2" :messages="$errors->get('asaas_wallet_id')" />
                    </div>
                    <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        {{ __('Guardar carteira') }}
                    </button>
                </form>
            </div>
        @endif

        <div class="mt-6 rounded-xl border border-sky-200/80 bg-sky-50/40 p-4 dark:border-sky-800/40 dark:bg-sky-950/20">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('PIX Manual (backup)') }}</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ __('Usado automaticamente caso você não tenha uma wallet Asaas activa, ou se escolher forçar o modo manual.') }}
            </p>

            <form class="mt-4 space-y-4" @submit.prevent="save">
                <fieldset>
                    <legend class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Preferência de recebimento') }}</legend>
                    <div class="mt-2 space-y-2">
                        @foreach (\App\Enums\PaymentMethodPreference::cases() as $pref)
                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900/60">
                                <input type="radio" name="payment_method_preference" value="{{ $pref->value }}" x-model="preference" class="mt-1 text-violet-600 focus:ring-violet-500" />
                                <span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $pref->label() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <x-input-label for="pix_manual_link" :value="__('Link ou chave PIX')" class="text-slate-700 dark:text-slate-200" />
                    <textarea
                        id="pix_manual_link"
                        x-model="link"
                        rows="3"
                        class="{{ $inputBase }}"
                        placeholder="{{ __('Cole aqui o link de pagamento ou a chave PIX copia-e-cola') }}"
                    ></textarea>
                </div>

                <div>
                    <x-input-label for="pix_qrcode" :value="__('Imagem do QR Code')" class="text-slate-700 dark:text-slate-200" />
                    <input id="pix_qrcode" type="file" accept="image/jpeg,image/png" class="mt-1.5 block w-full text-sm" @change="onQrChange" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('JPG ou PNG, máx. 2 MB.') }}</p>
                    <div class="mt-3" x-show="qrPreview || qrUrl" x-cloak>
                        <img :src="qrPreview || qrUrl" alt="{{ __('Pré-visualização do QR Code PIX') }}" class="h-40 w-40 rounded-xl border border-slate-200 bg-white object-contain p-2 dark:border-slate-600" />
                        <button type="button" class="mt-2 text-xs font-semibold text-rose-600" x-show="qrUrl && !qrPreview" @click="removeQr = true; qrUrl = null">{{ __('Remover QR actual') }}</button>
                    </div>
                </div>

                <p x-show="error" x-text="error" class="text-sm font-medium text-rose-600" x-cloak role="alert"></p>
                <p x-show="success" x-text="success" class="text-sm font-medium text-emerald-700" x-cloak role="status"></p>

                <button
                    type="submit"
                    class="inline-flex items-center rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-sky-500 disabled:opacity-60"
                    :disabled="saving"
                >
                    <span x-show="!saving">{{ __('Guardar PIX manual') }}</span>
                    <span x-show="saving" x-cloak>{{ __('A guardar…') }}</span>
                </button>
            </form>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('paymentSettingsForm', (config) => ({
                    preference: config.preference,
                    link: config.link || '',
                    qrUrl: config.qrUrl,
                    qrPreview: null,
                    qrFile: null,
                    removeQr: false,
                    badge: config.badge,
                    mode: config.mode,
                    saving: false,
                    error: '',
                    success: '',
                    saveUrl: config.saveUrl,
                    csrf: config.csrf,
                    onQrChange(event) {
                        const file = event.target.files?.[0];
                        this.error = '';
                        if (!file) return;
                        if (!['image/jpeg', 'image/png'].includes(file.type)) {
                            this.error = @js(__('Use uma imagem JPG ou PNG.'));
                            event.target.value = '';
                            return;
                        }
                        if (file.size > 2 * 1024 * 1024) {
                            this.error = @js(__('A imagem deve ter no máximo 2 MB.'));
                            event.target.value = '';
                            return;
                        }
                        this.qrFile = file;
                        this.removeQr = false;
                        this.qrPreview = URL.createObjectURL(file);
                    },
                    async save() {
                        this.saving = true;
                        this.error = '';
                        this.success = '';
                        try {
                            const body = new FormData();
                            body.append('payment_method_preference', this.preference);
                            body.append('pix_manual_link', this.link || '');
                            if (this.removeQr) body.append('remove_pix_qrcode', '1');
                            if (this.qrFile) body.append('pix_qrcode', this.qrFile);
                            const res = await fetch(this.saveUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body,
                            });
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok) {
                                const first = data.errors ? Object.values(data.errors).flat()[0] : (data.message || @js(__('Não foi possível guardar.')));
                                throw new Error(first);
                            }
                            this.badge = data.badge || this.badge;
                            this.mode = data.mode || this.mode;
                            this.qrUrl = data.pix_qrcode_url || null;
                            this.link = data.pix_manual_link || '';
                            this.qrPreview = null;
                            this.qrFile = null;
                            this.removeQr = false;
                            this.success = data.status || @js(__('Recebimento actualizado.'));
                        } catch (e) {
                            this.error = e.message || @js(__('Não foi possível guardar.'));
                        } finally {
                            this.saving = false;
                        }
                    },
                }));
            });
        </script>
    @endpush
@endif
