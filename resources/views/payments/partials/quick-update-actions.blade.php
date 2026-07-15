@php
    $currentStatus = $payment->status;
    $currentMethod = $payment->payment_method;

    $statusStyles = [
        \App\Enums\PaymentStatus::Paid->value => [
            'active' => 'border-emerald-500 bg-emerald-50 text-emerald-900 ring-emerald-500/30 dark:border-emerald-500 dark:bg-emerald-950/50 dark:text-emerald-100',
            'idle' => 'border-emerald-200/80 bg-white text-emerald-800 hover:border-emerald-400 hover:bg-emerald-50 dark:border-emerald-900 dark:bg-slate-900 dark:text-emerald-300 dark:hover:bg-emerald-950/40',
            'dot' => 'bg-emerald-500',
        ],
        \App\Enums\PaymentStatus::Pending->value => [
            'active' => 'border-amber-500 bg-amber-50 text-amber-900 ring-amber-500/30 dark:border-amber-500 dark:bg-amber-950/50 dark:text-amber-100',
            'idle' => 'border-amber-200/80 bg-white text-amber-900 hover:border-amber-400 hover:bg-amber-50 dark:border-amber-900 dark:bg-slate-900 dark:text-amber-200 dark:hover:bg-amber-950/40',
            'dot' => 'bg-amber-500',
        ],
        \App\Enums\PaymentStatus::PendingConfirmation->value => [
            'active' => 'border-sky-500 bg-sky-50 text-sky-900 ring-sky-500/30 dark:border-sky-500 dark:bg-sky-950/50 dark:text-sky-100',
            'idle' => 'border-sky-200/80 bg-white text-sky-900 hover:border-sky-400 hover:bg-sky-50 dark:border-sky-900 dark:bg-slate-900 dark:text-sky-200 dark:hover:bg-sky-950/40',
            'dot' => 'bg-sky-500',
        ],
        \App\Enums\PaymentStatus::Overdue->value => [
            'active' => 'border-rose-500 bg-rose-50 text-rose-900 ring-rose-500/30 dark:border-rose-500 dark:bg-rose-950/50 dark:text-rose-100',
            'idle' => 'border-rose-200/80 bg-white text-rose-800 hover:border-rose-400 hover:bg-rose-50 dark:border-rose-900 dark:bg-slate-900 dark:text-rose-300 dark:hover:bg-rose-950/40',
            'dot' => 'bg-rose-500',
        ],
        \App\Enums\PaymentStatus::Cancelled->value => [
            'active' => 'border-slate-500 bg-slate-100 text-slate-900 ring-slate-500/20 dark:border-slate-400 dark:bg-slate-800 dark:text-slate-100',
            'idle' => 'border-slate-200 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800',
            'dot' => 'bg-slate-400',
        ],
        \App\Enums\PaymentStatus::Refunded->value => [
            'active' => 'border-violet-500 bg-violet-50 text-violet-900 ring-violet-500/30 dark:border-violet-500 dark:bg-violet-950/50 dark:text-violet-100',
            'idle' => 'border-violet-200/80 bg-white text-violet-800 hover:border-violet-400 hover:bg-violet-50 dark:border-violet-900 dark:bg-slate-900 dark:text-violet-300 dark:hover:bg-violet-950/40',
            'dot' => 'bg-violet-500',
        ],
    ];

    $primaryStatuses = [\App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::PendingConfirmation, \App\Enums\PaymentStatus::Paid, \App\Enums\PaymentStatus::Overdue];
    $secondaryStatuses = [\App\Enums\PaymentStatus::Cancelled, \App\Enums\PaymentStatus::Refunded];

    $methodOptions = collect([
        ['value' => null, 'label' => __('Não informado'), 'clear' => true],
        ...collect(\App\Enums\PaymentMethod::cases())->map(fn ($m) => ['value' => $m, 'label' => $m->label(), 'clear' => false]),
    ]);
@endphp

<section
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60 lg:sticky lg:top-6"
    aria-labelledby="payment-quick-update-heading"
>
    <div class="border-b border-slate-100 bg-gradient-to-r from-violet-50/90 via-white to-emerald-50/60 px-5 py-4 dark:border-slate-700 dark:from-violet-950/30 dark:via-slate-900/80 dark:to-emerald-950/20">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-md shadow-violet-500/25" aria-hidden="true">
                <x-ui.icon name="currency" class="h-5 w-5" />
            </span>
            <div>
                <h2 id="payment-quick-update-heading" class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Atualizar pagamento') }}</h2>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Toque numa opção — a alteração é guardada de imediato.') }}</p>
            </div>
        </div>
    </div>

    <div class="space-y-6 p-5">
        {{-- Estado --}}
        <div>
            <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Estado da cobrança') }}</p>
            <div class="grid grid-cols-3 gap-2">
                @foreach ($primaryStatuses as $status)
                    @php
                        $isActive = $currentStatus === $status;
                        $styles = $statusStyles[$status->value];
                    @endphp
                    <form method="post" action="{{ route('payments.quick-update', $payment) }}" class="min-w-0">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="status" value="{{ $status->value }}" />
                        <button
                            type="submit"
                            @disabled($isActive)
                            class="flex w-full flex-col items-center gap-1.5 rounded-xl border px-2 py-3 text-center text-xs font-semibold transition disabled:cursor-default {{ $isActive ? $styles['active'].' ring-2' : $styles['idle'] }}"
                        >
                            <span class="h-2 w-2 rounded-full {{ $styles['dot'] }}" aria-hidden="true"></span>
                            <span>{{ $status->label() }}</span>
                            @if ($isActive)
                                <span class="text-[10px] font-medium opacity-70">{{ __('Atual') }}</span>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2">
                @foreach ($secondaryStatuses as $status)
                    @php
                        $isActive = $currentStatus === $status;
                        $styles = $statusStyles[$status->value];
                    @endphp
                    <form method="post" action="{{ route('payments.quick-update', $payment) }}">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="status" value="{{ $status->value }}" />
                        <button
                            type="submit"
                            @disabled($isActive)
                            class="flex w-full items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-xs font-semibold transition disabled:cursor-default {{ $isActive ? $styles['active'].' ring-2' : $styles['idle'] }}"
                        >
                            <span class="h-1.5 w-1.5 rounded-full {{ $styles['dot'] }}" aria-hidden="true"></span>
                            {{ $status->label() }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>

        <div class="border-t border-slate-100 dark:border-slate-700/80" aria-hidden="true"></div>

        {{-- Forma de pagamento --}}
        <div>
            <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Como foi pago') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($methodOptions as $option)
                    @php
                        $method = $option['value'];
                        $isActive = $option['clear']
                            ? $currentMethod === null
                            : $currentMethod === $method;
                    @endphp
                    <form method="post" action="{{ route('payments.quick-update', $payment) }}">
                        @csrf
                        @method('patch')
                        @if ($option['clear'])
                            <input type="hidden" name="clear_payment_method" value="1" />
                        @else
                            <input type="hidden" name="payment_method" value="{{ $method->value }}" />
                        @endif
                        <button
                            type="submit"
                            @disabled($isActive)
                            class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-2 text-xs font-semibold transition disabled:cursor-default {{ $isActive ? 'border-emerald-500 bg-emerald-50 text-emerald-900 ring-2 ring-emerald-500/20 dark:border-emerald-500 dark:bg-emerald-950/50 dark:text-emerald-100' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/30' }}"
                        >
                            @if ($method === \App\Enums\PaymentMethod::Pix)
                                <x-ui.icon name="banknote" class="h-3.5 w-3.5 shrink-0" />
                            @elseif ($method === \App\Enums\PaymentMethod::Card)
                                <x-ui.icon name="currency" class="h-3.5 w-3.5 shrink-0" />
                            @endif
                            {{ $option['label'] }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-600 dark:bg-slate-800/40">
            <a
                href="{{ route('payments.edit', $payment) }}"
                class="flex items-center justify-between gap-2 text-sm font-semibold text-violet-700 transition hover:text-violet-600 dark:text-violet-300 dark:hover:text-violet-200"
            >
                <span class="flex items-center gap-2">
                    <x-ui.icon name="pencil" class="h-4 w-4 shrink-0" />
                    {{ __('Editar valor e notas') }}
                </span>
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>
</section>
