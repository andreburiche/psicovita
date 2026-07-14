@props([
    'payment' => null,
    'patients' => null,
    'therapySessions' => null,
    'useParticipantBilling' => false,
    'sessionBillableParticipants' => null,
    'prefillSessionParticipantId' => null,
    'prefillSessionId' => null,
    'prefillPatientId' => null,
    'defaultAmount' => null,
])

@php
    $isEdit = $payment !== null;
    $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
    $participantService = app(\App\Services\SessionParticipantService::class);
    $billableParticipants = $sessionBillableParticipants ?? collect();
@endphp

@if (! $isEdit && $patients)
    <section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
        <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z" /></svg>
            </span>
            {{ __('Associar') }}
        </h3>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            @if ($useParticipantBilling)
                {{ __('Escolha o participante deste evento e vincule o pagamento à sessão.') }}
            @else
                {{ __('Escolha o paciente e, se quiser, vincule o pagamento a uma sessão.') }}
            @endif
        </p>

        <div class="mt-4 space-y-4">
            @if ($useParticipantBilling)
                <div>
                    <x-input-label for="session_participant_id" :value="__('Participante do evento')" class="text-slate-700 dark:text-slate-200" />
                    <select id="session_participant_id" name="session_participant_id" class="{{ $inputBase }}" required>
                        <option value="">{{ __('Selecione…') }}</option>
                        @foreach ($billableParticipants as $participant)
                            @php
                                $optionLabel = trim($participant->display_name);
                                if (filled($participant->email)) {
                                    $optionLabel .= ' · '.$participant->email;
                                }
                                $optionLabel .= ' — '.$participantService->participantBillingLabel($participant);
                            @endphp
                            <option
                                value="{{ $participant->id }}"
                                @selected((string) old('session_participant_id', $prefillSessionParticipantId ?? '') === (string) $participant->id)
                            >{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('session_participant_id')" />
                </div>

                <input type="hidden" name="therapy_session_id" value="{{ old('therapy_session_id', $prefillSessionId) }}" />
            @else
                <div>
                    <x-input-label for="patient_id" :value="__('Paciente')" class="text-slate-700 dark:text-slate-200" />
                    <select id="patient_id" name="patient_id" class="{{ $inputBase }}" required>
                        <option value="">{{ __('Selecione…') }}</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" @selected((string) old('patient_id', $prefillPatientId ?? request('patient_id')) === (string) $patient->id)>{{ $patient->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('patient_id')" />
                </div>
            @endif

            @if (! $useParticipantBilling && $therapySessions && $therapySessions->isNotEmpty())
                <div>
                    <x-input-label for="therapy_session_id" :value="__('Sessão (opcional)')" class="text-slate-700 dark:text-slate-200" />
                    <select id="therapy_session_id" name="therapy_session_id" class="{{ $inputBase }}">
                        <option value="">{{ __('Nenhuma') }}</option>
                        @foreach ($therapySessions as $session)
                            @php
                                $patientName = $session->patient?->name ?? __('Sem utente');
                                $label = $session->session_date->format('d/m/Y').' · '.(\Illuminate\Support\Str::of($session->session_time)->substr(0, 5)).' · '.$patientName;
                            @endphp
                            <option value="{{ $session->id }}" @selected((string) old('therapy_session_id', $prefillSessionId ?? request('therapy_session_id')) === (string) $session->id)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('therapy_session_id')" />
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Últimas sessões na sua agenda.') }}</p>
                </div>
            @elseif ($useParticipantBilling && $prefillSessionId)
                <div class="rounded-xl border border-violet-200/70 bg-violet-50/40 px-4 py-3 text-xs text-slate-600 dark:border-violet-900/40 dark:bg-violet-950/20 dark:text-slate-300">
                    {{ __('Pagamento vinculado à sessão #:id deste evento.', ['id' => $prefillSessionId]) }}
                </div>
            @endif
        </div>
    </section>
@elseif ($isEdit)
    <section class="rounded-2xl border border-slate-200/90 bg-gradient-to-br from-slate-50 to-emerald-50/40 p-5 ring-1 ring-slate-100 dark:border-slate-700 dark:from-slate-900 dark:to-emerald-950/30 dark:ring-slate-700/60">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Contexto') }}</h3>
        <dl class="mt-3 space-y-3 text-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <dt class="text-slate-500 dark:text-slate-400">{{ __('Paciente') }}</dt>
                <dd>
                    <a href="{{ route('patients.show', $payment->patient) }}" class="font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400">{{ $payment->patient->name }}</a>
                </dd>
            </div>
            @if ($payment->therapySession)
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Sessão') }}</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-100">
                        <a href="{{ route('therapy-sessions.show', $payment->therapySession) }}" class="hover:text-violet-600 dark:hover:text-violet-400">
                            {{ $payment->therapySession->session_date->format('d/m/Y') }}
                            · {{ \Illuminate\Support\Str::of($payment->therapySession->session_time)->substr(0, 5) }}
                        </a>
                    </dd>
                </div>
            @endif
        </dl>
        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ __('Para alterar o paciente ou a sessão, crie um novo registro ou edite a sessão.') }}</p>
    </section>
@endif

<section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0015.797 2.101c.738 0 1.374-.278 1.825-.896a3.75 3.75 0 00.125-4.45 3.75 3.75 0 00-3.546-2.209H2.25M2.25 12V9.75A2.25 2.25 0 014.5 7.5h15A2.25 2.25 0 0121.75 9.75V12m-9.303 3.75c.866 0 1.65-.318 2.25-.84M12 15.75c-.866 0-1.65-.318-2.25-.84m0 0c-.866 0-1.65-.318-2.25-.84M12 15.75V18m0 0h.008v.008H12V18z" /></svg>
        </span>
        {{ __('Valores e estado') }}
    </h3>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="amount" :value="__('Valor (R$)')" class="text-slate-700 dark:text-slate-200" />
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-400">R$</span>
                <input
                    id="amount"
                    name="amount"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    value="{{ old('amount', $payment?->amount ?? ($defaultAmount ?? config('payment.default_session_amount', 150))) }}"
                    class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-11 pr-3 text-sm text-slate-900 shadow-sm transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-violet-500"
                />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
        </div>
        <div>
            <x-input-label for="status" :value="__('Status')" class="text-slate-700 dark:text-slate-200" />
            <select id="status" name="status" class="{{ $inputBase }}" required>
                @foreach (\App\Enums\PaymentStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $isEdit ? $payment->status->value : \App\Enums\PaymentStatus::Pending->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>
    </div>

    <div class="mt-4">
        <x-input-label for="payment_method" :value="__('Meio de pagamento')" class="text-slate-700 dark:text-slate-200" />
        <select id="payment_method" name="payment_method" class="{{ $inputBase }}">
            <option value="">{{ __('Não informado') }}</option>
            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                <option value="{{ $method->value }}" @selected(old('payment_method', $payment?->payment_method?->value) === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('payment_method')" />
    </div>
</section>

<section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
        </span>
        {{ __('Observações') }}
    </h3>
    <div class="mt-4">
        <textarea id="notes" name="notes" rows="4" class="{{ $inputBase }}" placeholder="{{ __('Notas internas sobre este pagamento…') }}">{{ old('notes', $payment?->notes) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
    </div>
</section>
