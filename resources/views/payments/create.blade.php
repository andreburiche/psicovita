@php
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Novo pagamento') }}</x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero :title="__('Novo pagamento')" :subtitle="__('Registre um pagamento e associe, se quiser, a uma sessão recente.')" icon="currency" iconTone="teal">
                <x-slot name="actions">
                    <a
                        href="{{ route('payments.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Voltar') }}
                    </a>
                </x-slot>
            </x-page-hero>

            <form method="post" action="{{ route('payments.store') }}" class="space-y-6">
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

                @include('payments.partials.form-fields', [
                    'payment' => null,
                    'patients' => $patients,
                    'therapySessions' => $therapySessions ?? null,
                    'prefillSessionId' => $prefillSessionId ?? null,
                    'prefillPatientId' => $prefillPatientId ?? null,
                    'prefillSessionParticipantId' => $prefillSessionParticipantId ?? null,
                    'useParticipantBilling' => $useParticipantBilling ?? false,
                    'sessionBillableParticipants' => $sessionBillableParticipants ?? collect(),
                    'defaultAmount' => $defaultAmount ?? config('payment.default_session_amount', 150),
                ])

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                    <a href="{{ route('payments.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">
                        {{ __('Cancelar') }}
                    </a>
                    <x-primary-button class="justify-center sm:justify-start">{{ __('Salvar pagamento') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
