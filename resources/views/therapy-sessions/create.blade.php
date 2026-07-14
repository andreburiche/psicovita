@php
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Agendar sessão') }}</x-slot>

    @php
        $backUrl = route('therapy-sessions.index', array_filter(['month' => $returnMonth ?? null]));
        $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
        $monthLabel = null;
        if (! empty($returnMonth) && is_string($returnMonth) && preg_match('/^\d{4}-\d{2}$/', $returnMonth)) {
            try {
                $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $returnMonth)->translatedFormat('F Y');
            } catch (\Throwable) {
                $monthLabel = null;
            }
        }
    @endphp

    @php
        $oldFamilyGuests = [];
        if (is_array(old('family_guest_name'))) {
            foreach (old('family_guest_name') as $i => $guestName) {
                $email = (string) (old('family_guest_email')[$i] ?? '');
                if (trim((string) $guestName) !== '' || $email !== '') {
                    $oldFamilyGuests[] = [
                        'key' => 'f'.$i,
                        'name' => (string) $guestName,
                        'email' => $email,
                        'typeLabel' => __('Externo'),
                    ];
                }
            }
        }

        $oldObservers = [];
        if (is_array(old('session_observers'))) {
            foreach (old('session_observers') as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $source = (string) ($row['source'] ?? 'external');
                if ($source === 'professional' && ! empty($row['professional_id'])) {
                    $pro = ($professionals ?? collect())->firstWhere('id', (int) $row['professional_id']);
                    $oldObservers[] = [
                        'key' => 'o'.$i,
                        'source' => 'professional',
                        'professionalId' => (int) $row['professional_id'],
                        'name' => $pro?->name ?? __('Profissional'),
                        'email' => $pro?->email ?? '',
                        'typeLabel' => __('Profissional'),
                    ];
                } elseif ($source === 'external') {
                    $name = trim((string) ($row['name'] ?? ''));
                    $email = trim((string) ($row['email'] ?? ''));
                    if ($name === '' || $email === '') {
                        continue;
                    }
                    $oldObservers[] = [
                        'key' => 'o'.$i,
                        'source' => 'external',
                        'professionalId' => null,
                        'name' => (string) ($row['name'] ?? ''),
                        'email' => (string) ($row['email'] ?? ''),
                        'typeLabel' => __('Externo'),
                    ];
                }
            }
        }

        $professionalsForJs = ($professionals ?? collect())->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'email' => $p->email ?? '',
        ])->values()->all();

        $oldGroupPatientIds = array_values(array_map('intval', old('group_patient_ids', [])));
        $oldFamilyPatientIds = array_values(array_map('intval', old('family_patient_ids', [])));
        $defaultPaymentAmount = $defaultPaymentAmount ?? (float) config('payment.default_session_amount', 150);
        $autoChargeDefault = $autoChargeDefault ?? (bool) config('payment.auto_charge_on_session_created', true);
        $patientsNameMap = ($patientsNameMap ?? $patients->pluck('name', 'id'))->all();
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero :title="__('Agendar sessão')" :subtitle="__('1) Data · 2) Tipo · 3) Participantes · 4) Cobrança · 5) Salve.')" icon="calendar">
                @if ($monthLabel)
                    <x-slot name="eyebrow">{{ __('Mês: :month', ['month' => $monthLabel]) }}</x-slot>
                @endif
                <x-slot name="actions">
                    <a
                        href="{{ $backUrl }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ $monthLabel ? __('Voltar para :month', ['month' => $monthLabel]) : __('Voltar') }}
                    </a>
                    @if ($monthLabel)
                        <a
                            href="{{ route('schedule.index', ['month' => $returnMonth]) }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            {{ __('Abrir agenda') }}
                        </a>
                    @endif
                </x-slot>
            </x-page-hero>

            <form method="post" action="{{ route('therapy-sessions.store') }}" class="space-y-5" @submit="validateBeforeSubmit($event)" x-data="{
                type: @js(old('type', \App\Enums\TherapySessionType::Online->value)),
                sessionMode: @js(old('session_mode', \App\Enums\SessionMode::Individual->value)),
                primaryPatientId: @js((int) old('patient_id', $defaultPatientId ?? 0)),
                observerSource: @js(old('observer_source', ($professionals ?? collect())->isNotEmpty() ? 'professional' : 'external')),
                professionalsList: @js($professionalsForJs),
                observerDraft: { professionalId: '', name: '', email: '' },
                observerList: @js($oldObservers),
                observerError: '',
                familyExternalDraft: { name: '', email: '' },
                familyExternalList: @js($oldFamilyGuests),
                familyExternalError: '',
                familyPatientIds: @js($oldFamilyPatientIds),
                groupPatientIds: @js($oldGroupPatientIds),
                generatePayment: @js((bool) old('generate_payment', $autoChargeDefault)),
                paymentAmount: @js(old('payment_amount', $defaultPaymentAmount)),
                billingPatientId: @js((int) old('billing_patient_id', 0)),
                patientNames: @js($patientsNameMap),
                billingPatientOptions() {
                    if (this.sessionMode === @js(\App\Enums\SessionMode::Group->value)) {
                        return this.groupPatientIds.map((id) => ({ id, name: this.patientNames[String(id)] || @js(__('Utente')) }));
                    }
                    if (this.sessionMode === @js(\App\Enums\SessionMode::Family->value)) {
                        const ids = [...new Set([...this.familyPatientIds, this.primaryPatientId].filter((id) => Number(id) > 0))];
                        return ids.map((id) => ({ id, name: this.patientNames[String(id)] || @js(__('Utente')) }));
                    }
                    if (Number(this.primaryPatientId) > 0) {
                        return [{ id: this.primaryPatientId, name: this.patientNames[String(this.primaryPatientId)] || @js(__('Utente')) }];
                    }
                    return [];
                },
                canConfigureBilling() {
                    if (this.sessionMode === @js(\App\Enums\SessionMode::WithObserver->value)) {
                        return false;
                    }
                    return this.billingPatientOptions().length > 0;
                },
                showBillingPatientSelect() {
                    return this.sessionMode === @js(\App\Enums\SessionMode::Group->value)
                        || this.sessionMode === @js(\App\Enums\SessionMode::Family->value);
                },
                syncBillingPatientId() {
                    const options = this.billingPatientOptions();
                    if (options.length === 0) {
                        this.billingPatientId = 0;
                        return;
                    }
                    if (! options.some((option) => Number(option.id) === Number(this.billingPatientId))) {
                        this.billingPatientId = options[0].id;
                    }
                },
                attachObserver() {
                    this.observerError = '';
                    if (this.observerList.length >= 5) {
                        this.observerError = @js(__('Máximo de 5 observadores.'));
                        return;
                    }
                    if (this.observerSource === 'professional') {
                        const id = parseInt(this.observerDraft.professionalId, 10);
                        if (!id) {
                            this.observerError = @js(__('Selecione um profissional.'));
                            return;
                        }
                        if (this.observerList.some(o => o.source === 'professional' && o.professionalId === id)) {
                            this.observerError = @js(__('Este profissional já foi adicionado.'));
                            return;
                        }
                        const pro = this.professionalsList.find(p => p.id === id);
                        this.observerList.push({
                            key: 'o' + Date.now(),
                            source: 'professional',
                            professionalId: id,
                            name: pro?.name || @js(__('Profissional')),
                            email: pro?.email || '',
                            typeLabel: @js(__('Profissional')),
                        });
                        this.observerDraft.professionalId = '';
                    } else {
                        const name = (this.observerDraft.name || '').trim();
                        const email = (this.observerDraft.email || '').trim().toLowerCase();
                        if (!name || !email) {
                            this.observerError = @js(__('Informe nome e e-mail do observador.'));
                            return;
                        }
                        if (this.observerList.some(o => (o.email || '').toLowerCase() === email)) {
                            this.observerError = @js(__('Este e-mail já foi adicionado.'));
                            return;
                        }
                        this.observerList.push({
                            key: 'o' + Date.now(),
                            source: 'external',
                            professionalId: null,
                            name,
                            email,
                            typeLabel: @js(__('Externo')),
                        });
                        this.observerDraft.name = '';
                        this.observerDraft.email = '';
                    }
                },
                removeObserver(index) { this.observerList.splice(index, 1); },
                validObservers() {
                    return this.observerList.filter(o =>
                        o.source === 'professional'
                            ? Number(o.professionalId) > 0
                            : ((o.name || '').trim() !== '' && (o.email || '').trim() !== '')
                    );
                },
                validateBeforeSubmit(e) {
                    if (this.type !== @js(\App\Enums\TherapySessionType::Online->value) || this.sessionMode !== @js(\App\Enums\SessionMode::WithObserver->value)) {
                        return;
                    }
                    if (this.validObservers().length === 0) {
                        e.preventDefault();
                        this.observerError = @js(__('Adicione pelo menos um observador na lista antes de salvar.'));
                        this.$nextTick(() => document.getElementById('observer-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                    }
                },
                attachFamilyExternal() {
                    this.familyExternalError = '';
                    if (this.familyExternalList.length >= 5) {
                        this.familyExternalError = @js(__('Máximo de 5 convidados externos.'));
                        return;
                    }
                    const name = (this.familyExternalDraft.name || '').trim();
                    const email = (this.familyExternalDraft.email || '').trim().toLowerCase();
                    if (!name || !email) {
                        this.familyExternalError = @js(__('Informe nome e e-mail.'));
                        return;
                    }
                    if (this.familyExternalList.some(g => (g.email || '').toLowerCase() === email)) {
                        this.familyExternalError = @js(__('Este e-mail já foi adicionado.'));
                        return;
                    }
                    this.familyExternalList.push({
                        key: 'f' + Date.now(),
                        name,
                        email,
                        typeLabel: @js(__('Externo')),
                    });
                    this.familyExternalDraft.name = '';
                    this.familyExternalDraft.email = '';
                },
                removeFamilyExternal(index) { this.familyExternalList.splice(index, 1); },
                toggleFamilyPatient(id) {
                    const index = this.familyPatientIds.indexOf(id);
                    if (index >= 0) this.familyPatientIds.splice(index, 1);
                    else this.familyPatientIds.push(id);
                },
                isFamilyPatient(id) { return this.familyPatientIds.includes(id); },
                toggleGroupPatient(id) {
                    const index = this.groupPatientIds.indexOf(id);
                    if (index >= 0) {
                        this.groupPatientIds.splice(index, 1);
                    } else if (this.groupPatientIds.length < 12) {
                        this.groupPatientIds.push(id);
                    }
                },
                isGroupPatient(id) { return this.groupPatientIds.includes(id); },
                init() {
                    this.$watch('type', (value) => {
                        if (value !== @js(\App\Enums\TherapySessionType::Online->value)) {
                            this.sessionMode = @js(\App\Enums\SessionMode::Individual->value);
                        }
                    });
                    this.$watch('groupPatientIds', () => this.syncBillingPatientId());
                    this.$watch('familyPatientIds', () => this.syncBillingPatientId());
                    this.$watch('primaryPatientId', () => this.syncBillingPatientId());
                    this.$watch('sessionMode', () => this.syncBillingPatientId());
                    this.syncBillingPatientId();
                },
            }">
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

                {{-- 1. Agendamento --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50/80 via-white to-violet-50/50 px-5 py-4 dark:border-slate-700 dark:from-indigo-950/30 dark:via-slate-900/80 dark:to-violet-950/20">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-sm shadow-indigo-600/25" aria-hidden="true">1</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Data e horário') }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Quando a sessão vai acontecer.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="session_date" :value="__('Data')" class="text-slate-700 dark:text-slate-200" />
                            <input id="session_date" name="session_date" type="date" value="{{ old('session_date', $defaultSessionDate) }}" class="{{ $inputBase }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('session_date')" />
                        </div>
                        <div>
                            <x-input-label for="session_time" :value="__('Horário')" class="text-slate-700 dark:text-slate-200" />
                            <input id="session_time" name="session_time" type="time" value="{{ old('session_time', '09:00') }}" class="{{ $inputBase }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('session_time')" />
                        </div>
                    </div>
                </section>

                {{-- 2. Configuração: define o que aparece no resto do formulário --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
                    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-sm font-bold text-white shadow-sm shadow-emerald-600/25" aria-hidden="true">2</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Status e modalidade') }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Tipo de sessão — define os campos de participantes abaixo.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5 p-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="status" :value="__('Status')" class="text-slate-700 dark:text-slate-200" />
                                <select id="status" name="status" class="{{ $inputBase }}" required>
                                    @foreach (\App\Enums\TherapySessionStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected(old('status', \App\Enums\TherapySessionStatus::Scheduled->value) === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('status')" />
                            </div>
                            <div>
                                <x-input-label for="type" :value="__('Modalidade')" class="text-slate-700 dark:text-slate-200" />
                                <select id="type" name="type" x-model="type" class="{{ $inputBase }}" required>
                                    @foreach (\App\Enums\TherapySessionType::cases() as $type)
                                        <option value="{{ $type->value }}" @selected(old('type', \App\Enums\TherapySessionType::Online->value) === $type->value)>{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('type')" />
                            </div>
                        </div>

                        <div x-show="type === @js(\App\Enums\TherapySessionType::Online->value)" x-cloak class="rounded-xl border border-violet-200/70 bg-violet-50/40 p-4 dark:border-violet-900/40 dark:bg-violet-950/20">
                            <x-input-label for="session_mode" :value="__('Formato da sessão')" class="text-slate-800 dark:text-slate-100" />
                            <select id="session_mode" name="session_mode" x-model="sessionMode" class="{{ $inputBase }}">
                                @foreach ([\App\Enums\SessionMode::Individual, \App\Enums\SessionMode::WithObserver, \App\Enums\SessionMode::Family, \App\Enums\SessionMode::Group] as $mode)
                                    <option value="{{ $mode->value }}" @selected(old('session_mode', \App\Enums\SessionMode::Individual->value) === $mode->value)>{{ $mode->label() }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Individual: um utente. Escuta: supervisores. Família: vários utentes/convidados. Grupo: 2 a 12 utentes com link próprio.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('session_mode')" />
                        </div>

                        <div x-show="type !== @js(\App\Enums\TherapySessionType::Online->value)" x-cloak class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600 dark:border-slate-600 dark:bg-slate-800/50 dark:text-slate-300">
                            {{ __('Sessões presenciais usam o formato individual com um utente.') }}
                        </div>
                    </div>
                </section>

                {{-- 3. Participantes (dinâmico conforme formato) --}}
                <section
                    x-show="type !== @js(\App\Enums\TherapySessionType::Online->value) || sessionMode === @js(\App\Enums\SessionMode::Individual->value) || sessionMode === @js(\App\Enums\SessionMode::Group->value) || sessionMode === @js(\App\Enums\SessionMode::WithObserver->value) || sessionMode === @js(\App\Enums\SessionMode::Family->value)"
                    x-cloak
                    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60"
                >
                    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-sm font-bold text-white shadow-sm shadow-violet-600/25" aria-hidden="true">3</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Participantes') }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Utentes, observadores ou convidados conforme o formato escolhido.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5 p-5">
                        {{-- Individual: utente --}}
                        <div x-show="type !== @js(\App\Enums\TherapySessionType::Online->value) || sessionMode === @js(\App\Enums\SessionMode::Individual->value)" x-cloak>
                            <x-input-label for="patient_id" :value="__('Utente')" class="text-slate-700 dark:text-slate-200" />
                            <select
                                id="patient_id"
                                x-model.number="primaryPatientId"
                                class="{{ $inputBase }}"
                                :name="(type !== @js(\App\Enums\TherapySessionType::Online->value) || sessionMode === @js(\App\Enums\SessionMode::Individual->value)) ? 'patient_id' : null"
                                :required="type !== @js(\App\Enums\TherapySessionType::Online->value) || sessionMode === @js(\App\Enums\SessionMode::Individual->value)"
                            >
                                <option value="">{{ __('Selecione…') }}</option>
                                @foreach ($patients as $patient)
                                    <option value="{{ $patient->id }}" @selected((int) old('patient_id', $defaultPatientId ?? null) === (int) $patient->id)>{{ $patient->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('patient_id')" />
                        </div>

                        {{-- Grupo: membros --}}
                        <div x-show="type === @js(\App\Enums\TherapySessionType::Online->value) && sessionMode === @js(\App\Enums\SessionMode::Group->value)" x-cloak class="space-y-3">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Membros do grupo') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Selecione pelo menos 2 utentes. Cada um receberá um link pessoal.') }}</p>
                            <div class="max-h-56 space-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/50 p-2 dark:border-slate-600 dark:bg-slate-800/40">
                                @foreach ($patients as $patient)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 transition hover:bg-white dark:hover:bg-slate-800/80">
                                        <input
                                            type="checkbox"
                                            name="group_patient_ids[]"
                                            value="{{ $patient->id }}"
                                            class="rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                            @checked(in_array($patient->id, $oldGroupPatientIds, true))
                                            :checked="isGroupPatient({{ $patient->id }})"
                                            @change="toggleGroupPatient({{ $patient->id }})"
                                        />
                                        <span class="text-sm text-slate-800 dark:text-slate-100">{{ $patient->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs font-medium text-violet-600 dark:text-violet-400" x-show="groupPatientIds.length > 0">
                                <span x-text="groupPatientIds.length"></span> {{ __('selecionado(s)') }}
                            </p>
                            <x-input-error class="mt-2" :messages="$errors->get('group_patient_ids')" />
                        </div>

                        {{-- Escuta: observadores --}}
                        <div x-show="type === @js(\App\Enums\TherapySessionType::Online->value) && sessionMode === @js(\App\Enums\SessionMode::WithObserver->value)" x-cloak id="observer-section" class="space-y-4">
                            <div class="rounded-lg border border-indigo-200/80 bg-indigo-50/60 px-4 py-3 text-xs text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-100">
                                {{ __('Neste formato não é necessário selecionar utente — adicione um ou mais observadores abaixo.') }}
                            </div>

                            <div class="rounded-xl border border-indigo-200/60 bg-white p-4 dark:border-indigo-900/40 dark:bg-slate-900/60">
                                <p class="text-sm font-bold text-indigo-900 dark:text-indigo-200">{{ __('Observador') }}</p>

                                <div class="mt-4 space-y-4">
                                    <div>
                                        <x-input-label for="observer_source" :value="__('Tipo de observador')" class="text-slate-700 dark:text-slate-200" />
                                        <select id="observer_source" x-model="observerSource" class="{{ $inputBase }}">
                                            <option value="professional">{{ __('Profissional da clínica/equipa') }}</option>
                                            <option value="external">{{ __('Externo (nome e e-mail)') }}</option>
                                        </select>
                                    </div>

                                    <div x-show="observerSource === 'professional'" x-cloak>
                                        @if (($professionals ?? collect())->isEmpty())
                                            <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                                                {{ __('Não há outros profissionais na sua equipa. Use «Externo» ou convide colegas em Perfil → Equipa.') }}
                                            </p>
                                        @else
                                            <x-input-label for="observer_draft_professional" :value="__('Profissional observador')" class="text-slate-700 dark:text-slate-200" />
                                            <select id="observer_draft_professional" x-model="observerDraft.professionalId" class="{{ $inputBase }}">
                                                <option value="">{{ __('Selecione…') }}</option>
                                                @foreach ($professionals as $pro)
                                                    <option value="{{ $pro->id }}">{{ $pro->name }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    <div x-show="observerSource === 'external'" x-cloak class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <x-input-label for="observer_draft_name" :value="__('Nome do observador')" class="text-slate-700 dark:text-slate-200" />
                                            <input id="observer_draft_name" type="text" x-model="observerDraft.name" class="{{ $inputBase }}" autocomplete="name" />
                                        </div>
                                        <div>
                                            <x-input-label for="observer_draft_email" :value="__('E-mail do observador')" class="text-slate-700 dark:text-slate-200" />
                                            <input id="observer_draft_email" type="email" x-model="observerDraft.email" class="{{ $inputBase }}" autocomplete="email" />
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4 dark:border-slate-700">
                                        <button
                                            type="button"
                                            @click="attachObserver()"
                                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                                        >
                                            <x-ui.icon name="plus" class="h-4 w-4" />
                                            {{ __('Anexar') }}
                                        </button>
                                        <p class="text-xs text-slate-500 dark:text-slate-400" x-show="observerList.length > 0">
                                            <span x-text="observerList.length"></span> {{ __('na lista') }}
                                        </p>
                                    </div>
                                    <p x-show="observerError" x-text="observerError" class="text-xs text-rose-600 dark:text-rose-400" role="alert"></p>
                                    <x-input-error class="mt-2" :messages="$errors->get('session_observers')" />

                                    <div class="sr-only" aria-hidden="true">
                                        <template x-for="(obs, index) in observerList" :key="'hidden-' + obs.key">
                                            <div>
                                                <input type="hidden" :name="'session_observers[' + index + '][source]'" :value="obs.source">
                                                <input type="hidden" :name="'session_observers[' + index + '][professional_id]'" :value="obs.professionalId ?? ''">
                                                <input type="hidden" :name="'session_observers[' + index + '][name]'" :value="obs.name ?? ''">
                                                <input type="hidden" :name="'session_observers[' + index + '][email]'" :value="obs.email ?? ''">
                                            </div>
                                        </template>
                                    </div>

                                    <div x-show="observerList.length > 0" x-cloak class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                            <thead class="bg-slate-50 dark:bg-slate-800/80">
                                                <tr>
                                                    <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Nome') }}</th>
                                                    <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('E-mail') }}</th>
                                                    <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Tipo') }}</th>
                                                    <th class="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900/40">
                                                <template x-for="(obs, index) in observerList" :key="obs.key">
                                                    <tr>
                                                        <td class="px-4 py-2.5 font-medium text-slate-900 dark:text-slate-100" x-text="obs.name"></td>
                                                        <td class="px-4 py-2.5 text-slate-600 dark:text-slate-300" x-text="obs.email || '—'"></td>
                                                        <td class="px-4 py-2.5 text-slate-600 dark:text-slate-300" x-text="obs.typeLabel"></td>
                                                        <td class="px-4 py-2.5 text-right">
                                                            <button type="button" @click="removeObserver(index)" class="text-xs font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-400">{{ __('Remover') }}</button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Família: utentes + externos --}}
                        <div x-show="type === @js(\App\Enums\TherapySessionType::Online->value) && sessionMode === @js(\App\Enums\SessionMode::Family->value)" x-cloak class="space-y-4">
                            <div class="rounded-xl border border-teal-200/70 bg-teal-50/40 p-4 dark:border-teal-900/40 dark:bg-teal-950/20">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Utentes do sistema') }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Familiares ou parceiros já cadastrados.') }}</p>
                                <div class="mt-3 max-h-48 space-y-1 overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 dark:border-slate-600 dark:bg-slate-900/60">
                                    @foreach ($patients as $patient)
                                        <label
                                            class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
                                            x-show="!primaryPatientId || primaryPatientId !== {{ $patient->id }}"
                                        >
                                            <input
                                                type="checkbox"
                                                name="family_patient_ids[]"
                                                value="{{ $patient->id }}"
                                                class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                                :checked="isFamilyPatient({{ $patient->id }})"
                                                @change="toggleFamilyPatient({{ $patient->id }})"
                                            />
                                            <span class="text-sm text-slate-800 dark:text-slate-100">{{ $patient->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('family_patient_ids')" />
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-600 dark:bg-slate-800/40">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Convidados externos') }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Pessoas que ainda não estão na lista de utentes.') }}</p>
                                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="family_external_name" :value="__('Nome')" class="text-slate-700 dark:text-slate-200" />
                                        <input id="family_external_name" type="text" x-model="familyExternalDraft.name" class="{{ $inputBase }}" autocomplete="name" />
                                    </div>
                                    <div>
                                        <x-input-label for="family_external_email" :value="__('E-mail')" class="text-slate-700 dark:text-slate-200" />
                                        <input id="family_external_email" type="email" x-model="familyExternalDraft.email" class="{{ $inputBase }}" autocomplete="email" />
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4 dark:border-slate-600">
                                    <button type="button" @click="attachFamilyExternal()" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500">
                                        <x-ui.icon name="plus" class="h-4 w-4" />
                                        {{ __('Anexar') }}
                                    </button>
                                    <p class="text-xs text-slate-500" x-show="familyExternalList.length > 0">
                                        <span x-text="familyExternalList.length"></span> {{ __('na lista') }}
                                    </p>
                                </div>
                                <p x-show="familyExternalError" x-text="familyExternalError" class="mt-2 text-xs text-rose-600 dark:text-rose-400" role="alert"></p>

                                <div class="sr-only" aria-hidden="true">
                                    <template x-for="(guest, index) in familyExternalList" :key="'hidden-f-' + guest.key">
                                        <div>
                                            <input type="hidden" :name="'family_guest_name[' + index + ']'" :value="guest.name ?? ''">
                                            <input type="hidden" :name="'family_guest_email[' + index + ']'" :value="guest.email ?? ''">
                                        </div>
                                    </template>
                                </div>

                                <div x-show="familyExternalList.length > 0" x-cloak class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-900/60">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                        <thead class="bg-slate-50 dark:bg-slate-800/80">
                                            <tr>
                                                <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Nome') }}</th>
                                                <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('E-mail') }}</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            <template x-for="(guest, index) in familyExternalList" :key="guest.key">
                                                <tr>
                                                    <td class="px-4 py-2.5 font-medium text-slate-900 dark:text-slate-100" x-text="guest.name"></td>
                                                    <td class="px-4 py-2.5 text-slate-600 dark:text-slate-300" x-text="guest.email"></td>
                                                    <td class="px-4 py-2.5 text-right">
                                                        <button type="button" @click="removeFamilyExternal(index)" class="text-xs font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-400">{{ __('Remover') }}</button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('family_guest_name')" />
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 4. Cobrança --}}
                <section
                    x-show="canConfigureBilling()"
                    x-cloak
                    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60"
                >
                    <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50/80 via-white to-teal-50/50 px-5 py-4 dark:border-slate-700 dark:from-emerald-950/30 dark:via-slate-900/80 dark:to-teal-950/20">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-sm font-bold text-white shadow-sm shadow-emerald-600/25" aria-hidden="true">4</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Cobrança') }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Gere um pagamento pendente ao salvar o agendamento.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-5">
                        <input type="hidden" name="generate_payment" value="0" />
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-emerald-200/70 bg-emerald-50/40 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                            <input
                                type="checkbox"
                                name="generate_payment"
                                value="1"
                                class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                x-model="generatePayment"
                            />
                            <span>
                                <span class="block text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Gerar cobrança para esta sessão') }}</span>
                                <span class="mt-1 block text-xs text-slate-600 dark:text-slate-400">{{ __('Cria um pagamento pendente vinculado ao agendamento.') }}</span>
                            </span>
                        </label>
                        <x-input-error class="mt-2" :messages="$errors->get('generate_payment')" />

                        <div x-show="generatePayment" x-cloak class="grid gap-4 sm:grid-cols-2">
                            <div x-show="showBillingPatientSelect()" x-cloak>
                                <x-input-label for="billing_patient_id" :value="__('Utente responsável pela cobrança')" class="text-slate-700 dark:text-slate-200" />
                                <select
                                    id="billing_patient_id"
                                    name="billing_patient_id"
                                    x-model.number="billingPatientId"
                                    class="{{ $inputBase }}"
                                >
                                    <template x-for="option in billingPatientOptions()" :key="'bill-' + option.id">
                                        <option :value="option.id" x-text="option.name"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-show="sessionMode === @js(\App\Enums\SessionMode::Group->value)">
                                    {{ __('Em grupo, apenas este utente recebe a cobrança automática. Os demais podem ser cobrados depois em Pagamentos.') }}
                                </p>
                                <x-input-error class="mt-2" :messages="$errors->get('billing_patient_id')" />
                            </div>

                            <div>
                                <x-input-label for="payment_amount" :value="__('Valor da cobrança (R$)')" class="text-slate-700 dark:text-slate-200" />
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-400">R$</span>
                                    <input
                                        id="payment_amount"
                                        name="payment_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        x-model="paymentAmount"
                                        class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-11 pr-3 text-sm text-slate-900 shadow-sm transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-violet-500"
                                    />
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('payment_amount')" />
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 5. Notas --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
                    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-500 text-sm font-bold text-white shadow-sm" aria-hidden="true">5</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Notas') }} <span class="font-normal text-slate-400">({{ __('opcional') }})</span></h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Registo interno — não é partilhado com participantes.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5">
                        <textarea id="notes" name="notes" rows="3" class="{{ $inputBase }}" placeholder="{{ __('Ex.: tema da sessão, preparação, lembretes…') }}">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>
                </section>

                <div class="sticky bottom-0 z-10 -mx-4 flex flex-col-reverse gap-3 border-t border-slate-200 bg-white/95 px-4 py-4 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/95 sm:static sm:mx-0 sm:rounded-2xl sm:border sm:px-5 sm:shadow-sm">
                    <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Cancelar') }}</a>
                    <x-primary-button class="justify-center">{{ __('Salvar sessão') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
