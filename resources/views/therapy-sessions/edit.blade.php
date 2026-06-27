@php
    use App\Enums\SessionMode;
    use App\Enums\TherapySessionStatus;
    use App\Enums\TherapySessionType;

    $patientLabel = $session->patient?->name ?? ($session->session_mode === SessionMode::WithObserver
        ? __('Escuta / supervisão')
        : __('Sessão sem utente principal'));
    $requiresPatient = ($session->session_mode ?? SessionMode::Individual) === SessionMode::Individual;
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Editar sessão') }}</x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Editar sessão')"
                :subtitle="__('Altere data, horário, modalidade ou notas desta consulta.')"
                icon="clock"
            >
                <x-slot name="eyebrow">{{ $patientLabel }}</x-slot>
                <x-slot name="actions">
                    <a
                        href="{{ route('therapy-sessions.show', $session) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Ver sessão') }}
                    </a>
                </x-slot>
            </x-page-hero>

            <form method="post" action="{{ route('therapy-sessions.update', $session) }}" class="space-y-6 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60 sm:p-8">
                @csrf
                @method('put')

                @if ($requiresPatient)
                    <div>
                        <x-input-label for="patient_id" :value="__('Paciente')" />
                        <select id="patient_id" name="patient_id" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" required>
                            @foreach ($patients as $patient)
                                <option value="{{ $patient->id }}" @selected(old('patient_id', $session->patient_id) == $patient->id)>{{ $patient->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('patient_id')" />
                    </div>
                @else
                    <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                        {{ __('Formato :mode — o utente principal é definido pelos participantes da sessão, não por este formulário.', ['mode' => ($session->session_mode ?? SessionMode::Individual)->label()]) }}
                    </p>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="session_date" :value="__('Data')" />
                        <x-text-input id="session_date" name="session_date" type="date" class="mt-1.5 block w-full rounded-xl" :value="old('session_date', $session->session_date->format('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('session_date')" />
                    </div>
                    <div>
                        <x-input-label for="session_time" :value="__('Horário')" />
                        @php
                            $st = $session->session_time;
                            $timeValue = $st instanceof \DateTimeInterface ? $st->format('H:i') : substr((string) $st, 0, 5);
                        @endphp
                        <x-text-input id="session_time" name="session_time" type="time" class="mt-1.5 block w-full rounded-xl" :value="old('session_time', $timeValue)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('session_time')" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" required>
                            @foreach (TherapySessionStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $session->status->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                    <div>
                        <x-input-label for="type" :value="__('Modalidade')" />
                        <select id="type" name="type" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" required>
                            @foreach (TherapySessionType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('type', $session->type->value) === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('type')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="notes" :value="__('Notas')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">{{ old('notes', $session->notes) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('therapy-sessions.show', $session) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Cancelar') }}</a>
                    <x-primary-button>{{ __('Guardar alterações') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
