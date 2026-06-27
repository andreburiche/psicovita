@php
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;

    $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
@endphp

@if ($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
    <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50/90 via-teal-50/50 to-white px-5 py-4 dark:border-slate-700 dark:from-emerald-950/40 dark:via-teal-950/30 dark:to-slate-900/90 sm:px-6">
        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Alterar pagamento') }}</h2>
        <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
            {{ __('Atualize valor, estado, meio de pagamento e observações. Paciente e sessão não podem ser alterados neste formulário.') }}
        </p>
    </div>

    {{-- Passo 1 --}}
    <div class="px-5 py-5 sm:px-6 sm:py-6">
        <div class="flex gap-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white shadow-sm" aria-hidden="true">1</span>
            <div class="min-w-0 flex-1">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    <x-ui.icon name="currency" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    {{ __('Valor e estado') }}
                </h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="amount" :value="__('Valor (R$)')" class="text-slate-700 dark:text-slate-200" />
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-400">R$</span>
                            <input
                                id="amount"
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                value="{{ old('amount', $payment->amount) }}"
                                class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-11 pr-3 text-sm text-slate-900 shadow-sm transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-violet-500"
                            />
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Estado')" class="text-slate-700 dark:text-slate-200" />
                        <select id="status" name="status" class="{{ $inputBase }}" required>
                            @foreach (PaymentStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $payment->status->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 dark:border-slate-700/80" aria-hidden="true"></div>

    {{-- Passo 2 --}}
    <div class="px-5 py-5 sm:px-6 sm:py-6">
        <div class="flex gap-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white shadow-sm" aria-hidden="true">2</span>
            <div class="min-w-0 flex-1">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    <x-ui.icon name="briefcase" class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                    {{ __('Meio de pagamento') }}
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Opcional — indique como o paciente pagou ou pretende pagar.') }}</p>
                <div class="mt-4 max-w-md">
                    <x-input-label for="payment_method" :value="__('Forma de pagamento')" class="text-slate-700 dark:text-slate-200" />
                    <select id="payment_method" name="payment_method" class="{{ $inputBase }}">
                        <option value="">{{ __('Não informado') }}</option>
                        @foreach (PaymentMethod::cases() as $method)
                            <option value="{{ $method->value }}" @selected(old('payment_method', $payment->payment_method?->value) === $method->value)>{{ $method->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('payment_method')" />
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 dark:border-slate-700/80" aria-hidden="true"></div>

    {{-- Passo 3 --}}
    <div class="px-5 py-5 sm:px-6 sm:py-6">
        <div class="flex gap-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white shadow-sm" aria-hidden="true">3</span>
            <div class="min-w-0 flex-1">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    <x-ui.icon name="document-text" class="h-4 w-4 text-violet-600 dark:text-violet-400" />
                    {{ __('Observações') }}
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold normal-case tracking-normal text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ __('opcional') }}</span>
                </h3>
                <div class="mt-4">
                    <textarea id="notes" name="notes" rows="4" class="{{ $inputBase }}" placeholder="{{ __('Notas internas sobre este pagamento…') }}">{{ old('notes', $payment->notes) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/40 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
        <a
            href="{{ route('payments.show', $payment) }}"
            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
        >
            {{ __('Cancelar') }}
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500"
        >
            <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
            {{ __('Guardar alterações') }}
        </button>
    </div>
</div>
