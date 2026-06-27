<x-patient-layout>
    <x-slot name="header">{{ __('Pagamentos') }}</x-slot>

    <x-patient-portal-shell>
    <x-patient-portal-breadcrumb :items="[
        ['label' => __('Início'), 'href' => route('patient.home')],
        ['label' => __('Pagamentos')],
    ]" />

    <x-patient-portal-hero
        :title="__('Pagamentos de sessões')"
        :subtitle="__('Consulte cobranças e pague de forma segura na plataforma — PIX ou cartão.')"
        icon="currency"
    />

    @if ($pendingTotal > 0)
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-amber-200/80 bg-amber-50/80 p-4 dark:border-amber-800/40 dark:bg-amber-950/30">
                <p class="text-xs font-bold uppercase tracking-wide text-amber-800 dark:text-amber-300">{{ __('Total pendente') }}</p>
                <p class="mt-1 text-2xl font-extrabold tabular-nums text-amber-950 dark:text-amber-100">R$ {{ number_format($pendingTotal, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/80">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Cobranças') }}</p>
                <p class="mt-1 text-2xl font-extrabold tabular-nums text-slate-900 dark:text-white">{{ $payments->total() }}</p>
            </div>
        </div>
    @endif

    @if ($payments->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-14 text-center dark:border-slate-600 dark:bg-slate-900/40">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200/80 text-slate-600 dark:bg-slate-800 dark:text-slate-300" aria-hidden="true">
                <x-ui.icon name="currency" class="h-6 w-6" />
            </span>
            <p class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Nenhuma cobrança registada') }}</p>
            <p class="mx-auto mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400">{{ __('Quando o profissional registar pagamentos associados à sua ficha, aparecem aqui.') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($payments as $payment)
                @php
                    $badgeVariant = match ($payment->status) {
                        \App\Enums\PaymentStatus::Paid => 'success',
                        \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::Overdue => 'warning',
                        default => 'neutral',
                    };
                @endphp
                <article class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 transition hover:border-emerald-200 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50 dark:hover:border-emerald-800">
                    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-xl font-extrabold tabular-nums text-slate-900 dark:text-white">
                                    R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}
                                </p>
                                <x-ui.badge :variant="$badgeVariant">{{ $payment->status->label() }}</x-ui.badge>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
                                @if ($payment->therapySession)
                                    {{ __('Sessão') }} {{ $payment->therapySession->session_date->format('d/m/Y') }}
                                    · {{ \Illuminate\Support\Str::of($payment->therapySession->session_time)->substr(0, 5) }}
                                @else
                                    {{ __('Cobrança') }} #{{ $payment->id }}
                                @endif
                                @if ($payment->payment_method)
                                    · {{ $payment->payment_method->label() }}
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <a
                                href="{{ route('patient.payments.show', $payment) }}"
                                class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                {{ __('Detalhes') }}
                            </a>
                            @can('pay', $payment)
                                @if ($payment->payment_method === null)
                                    <a
                                        href="{{ route('patient.payments.show', $payment) }}"
                                        class="inline-flex items-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:from-violet-500 hover:to-indigo-500"
                                    >
                                        {{ __('Escolher pagamento') }}
                                    </a>
                                @else
                                    <form method="POST" action="{{ route('patient.payments.pay', $payment) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:from-emerald-500 hover:to-teal-500"
                                        >
                                            {{ __('Pagar agora') }}
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pt-2">
            {{ $payments->links('pagination.psiconecta') }}
        </div>
    @endif
    </x-patient-portal-shell>
</x-patient-layout>
