@php
    $badgeVariant = match ($payment->status) {
        \App\Enums\PaymentStatus::Paid => 'success',
        \App\Enums\PaymentStatus::Pending => 'warning',
        \App\Enums\PaymentStatus::Overdue => 'danger',
        \App\Enums\PaymentStatus::Cancelled => 'neutral',
        \App\Enums\PaymentStatus::Refunded => 'neutral',
    };
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Editar pagamento') }}</x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Editar pagamento')"
                :subtitle="__('Ajuste valor, estado e detalhes. O paciente e a sessão vinculada permanecem inalterados.')"
                icon="currency"
                iconTone="teal"
            >
                <x-slot name="eyebrow">
                    #{{ $payment->id }}
                    · R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}
                    · {{ $payment->patient->name }}
                </x-slot>
                <x-slot name="actions">
                    <a
                        href="{{ route('payments.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Lista financeira') }}
                    </a>
                    <a
                        href="{{ route('payments.show', $payment) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Ver detalhes') }}
                    </a>
                </x-slot>
            </x-page-hero>

            <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-8">
                    <form method="post" action="{{ route('payments.update', $payment) }}" class="space-y-6">
                        @csrf
                        @method('put')
                        @include('payments.partials.edit-form', ['payment' => $payment])
                    </form>
                </div>

                <aside class="space-y-6 lg:col-span-4">
                    {{-- Contexto --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-emerald-50/40 px-5 py-4 dark:border-slate-700 dark:from-slate-900 dark:to-emerald-950/30">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Contexto fixo') }}</h2>
                        </div>
                        <dl class="space-y-4 px-5 py-5 text-sm">
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Paciente') }}</dt>
                                <dd class="mt-1">
                                    <a href="{{ route('patients.show', $payment->patient) }}" class="font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400">
                                        {{ $payment->patient->name }}
                                    </a>
                                </dd>
                            </div>
                            @if ($payment->therapySession)
                                <div>
                                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Sessão vinculada') }}</dt>
                                    <dd class="mt-1">
                                        <a href="{{ route('therapy-sessions.show', $payment->therapySession) }}" class="font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400">
                                            {{ $payment->therapySession->session_date->format('d/m/Y') }}
                                            · {{ \Illuminate\Support\Str::of($payment->therapySession->session_time)->substr(0, 5) }}
                                        </a>
                                    </dd>
                                </div>
                            @else
                                <div>
                                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Sessão') }}</dt>
                                    <dd class="mt-1 text-slate-500 dark:text-slate-400">{{ __('Sem sessão associada') }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Estado atual') }}</dt>
                                <dd class="mt-1.5"><x-ui.badge :variant="$badgeVariant">{{ $payment->status->label() }}</x-ui.badge></dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Registado em') }}</dt>
                                <dd class="mt-1 font-medium text-slate-800 dark:text-slate-200">{{ $payment->created_at->format('d/m/Y H:i') }}</dd>
                            </div>
                        </dl>
                        <p class="border-t border-slate-100 px-5 py-3 text-[11px] leading-relaxed text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            {{ __('Para alterar o paciente ou a sessão, registre um novo pagamento.') }}
                        </p>
                    </section>

                    {{-- Atalhos de estado --}}
                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60"
                        x-data="{
                            setStatus(value) {
                                const el = document.getElementById('status');
                                if (el) el.value = value;
                            },
                        }"
                    >
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Alterar estado rapidamente') }}</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Define o campo Estado no formulário. Confirme com Guardar.') }}</p>
                        <ul class="mt-4 space-y-2" role="list">
                            @foreach (\App\Enums\PaymentStatus::cases() as $status)
                                @php
                                    $quickVariant = match ($status) {
                                        \App\Enums\PaymentStatus::Paid => 'hover:border-emerald-300 hover:bg-emerald-50 dark:hover:border-emerald-800 dark:hover:bg-emerald-950/30',
                                        \App\Enums\PaymentStatus::Pending => 'hover:border-amber-300 hover:bg-amber-50 dark:hover:border-amber-800 dark:hover:bg-amber-950/30',
                                        \App\Enums\PaymentStatus::Overdue => 'hover:border-rose-300 hover:bg-rose-50 dark:hover:border-rose-800 dark:hover:bg-rose-950/30',
                                        \App\Enums\PaymentStatus::Cancelled => 'hover:border-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                                        \App\Enums\PaymentStatus::Refunded => 'hover:border-violet-300 hover:bg-violet-50 dark:hover:border-violet-800 dark:hover:bg-violet-950/30',
                                    };
                                @endphp
                                <li>
                                    <button
                                        type="button"
                                        @click="setStatus(@js($status->value))"
                                        class="flex w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 {{ $quickVariant }}"
                                    >
                                        {{ $status->label() }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    {{-- Excluir --}}
                    <section class="overflow-hidden rounded-2xl border border-rose-200/80 bg-rose-50/50 p-5 dark:border-rose-900/40 dark:bg-rose-950/20">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-rose-900 dark:text-rose-200">{{ __('Zona de risco') }}</h2>
                        <p class="mt-2 text-xs leading-relaxed text-rose-900/80 dark:text-rose-100/80">
                            {{ __('Remover o registo financeiro é permanente e não pode ser desfeito.') }}
                        </p>
                        <x-confirm-form
                            method="post"
                            action="{{ route('payments.destroy', $payment) }}"
                            :title="__('Remover pagamento?')"
                            :message="__('Este registro financeiro será excluído permanentemente.')"
                            :confirm-label="__('Sim, remover')"
                            variant="danger"
                            :validate="false"
                            class="mt-4"
                        >
                            @csrf
                            @method('delete')
                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-sm font-semibold text-rose-800 transition hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200 dark:hover:bg-rose-950/60"
                            >
                                {{ __('Excluir pagamento') }}
                            </button>
                        </x-confirm-form>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
