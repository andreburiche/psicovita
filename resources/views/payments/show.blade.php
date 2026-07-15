<x-app-layout>
    <x-slot name="header">{{ __('Pagamento') }}</x-slot>

    @php
        $badgeVariant = match ($payment->status) {
            \App\Enums\PaymentStatus::Paid => 'success',
            \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::PendingConfirmation => 'warning',
            \App\Enums\PaymentStatus::Overdue => 'danger',
            \App\Enums\PaymentStatus::Cancelled => 'neutral',
            \App\Enums\PaymentStatus::Refunded => 'violet',
        };

        $heroSurface = match ($payment->status) {
            \App\Enums\PaymentStatus::Paid => 'border-emerald-200/90 bg-gradient-to-br from-emerald-50 via-teal-50 to-emerald-100 dark:border-emerald-800/60 dark:from-emerald-950/50 dark:via-teal-950/40 dark:to-slate-900',
            \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::PendingConfirmation => 'border-amber-200/90 bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100 dark:border-amber-800/60 dark:from-amber-950/50 dark:via-orange-950/40 dark:to-slate-900',
            \App\Enums\PaymentStatus::Overdue => 'border-rose-200/90 bg-gradient-to-br from-rose-50 via-red-50 to-rose-100 dark:border-rose-800/60 dark:from-rose-950/50 dark:via-red-950/40 dark:to-slate-900',
            \App\Enums\PaymentStatus::Cancelled => 'border-slate-200/90 bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 dark:border-slate-700 dark:from-slate-900 dark:via-slate-800 dark:to-slate-950',
            \App\Enums\PaymentStatus::Refunded => 'border-violet-200/90 bg-gradient-to-br from-violet-50 via-indigo-50 to-violet-100 dark:border-violet-800/60 dark:from-violet-950/50 dark:via-indigo-950/40 dark:to-slate-900',
        };

        $heroAccent = match ($payment->status) {
            \App\Enums\PaymentStatus::Paid => 'text-emerald-900 dark:text-emerald-50',
            \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::PendingConfirmation => 'text-amber-950 dark:text-amber-50',
            \App\Enums\PaymentStatus::Overdue => 'text-rose-950 dark:text-rose-50',
            \App\Enums\PaymentStatus::Cancelled => 'text-slate-800 dark:text-slate-100',
            \App\Enums\PaymentStatus::Refunded => 'text-violet-950 dark:text-violet-50',
        };

        $heroMuted = match ($payment->status) {
            \App\Enums\PaymentStatus::Paid => 'text-emerald-800/75 dark:text-emerald-200/80',
            \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::PendingConfirmation => 'text-amber-900/75 dark:text-amber-200/80',
            \App\Enums\PaymentStatus::Overdue => 'text-rose-900/75 dark:text-rose-200/80',
            \App\Enums\PaymentStatus::Cancelled => 'text-slate-600 dark:text-slate-300',
            \App\Enums\PaymentStatus::Refunded => 'text-violet-900/75 dark:text-violet-200/80',
        };

        $heroSubcard = match ($payment->status) {
            \App\Enums\PaymentStatus::Paid => 'bg-white/70 ring-emerald-200/80 dark:bg-white/5 dark:ring-emerald-800/50',
            \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::PendingConfirmation => 'bg-white/80 ring-amber-200/80 dark:bg-white/5 dark:ring-amber-800/50',
            \App\Enums\PaymentStatus::Overdue => 'bg-white/80 ring-rose-200/80 dark:bg-white/5 dark:ring-rose-800/50',
            \App\Enums\PaymentStatus::Cancelled => 'bg-white/70 ring-slate-200/80 dark:bg-white/5 dark:ring-slate-700/50',
            \App\Enums\PaymentStatus::Refunded => 'bg-white/80 ring-violet-200/80 dark:bg-white/5 dark:ring-violet-800/50',
        };

        $methodChip = match ($payment->status) {
            \App\Enums\PaymentStatus::Paid => 'bg-emerald-100 text-emerald-900 ring-emerald-300/60 dark:bg-emerald-950/60 dark:text-emerald-100 dark:ring-emerald-700/50',
            \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::PendingConfirmation => 'bg-amber-100 text-amber-950 ring-amber-300/60 dark:bg-amber-950/60 dark:text-amber-100 dark:ring-amber-700/50',
            \App\Enums\PaymentStatus::Overdue => 'bg-rose-100 text-rose-950 ring-rose-300/60 dark:bg-rose-950/60 dark:text-rose-100 dark:ring-rose-700/50',
            \App\Enums\PaymentStatus::Cancelled => 'bg-slate-100 text-slate-800 ring-slate-300/60 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-600/50',
            \App\Enums\PaymentStatus::Refunded => 'bg-violet-100 text-violet-950 ring-violet-300/60 dark:bg-violet-950/60 dark:text-violet-100 dark:ring-violet-700/50',
        };

        $patientInitial = mb_strtoupper(mb_substr($payment->patient->name, 0, 1));
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:space-y-8 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Pagamento #:id', ['id' => $payment->id])"
                :subtitle="__('Cobrança de :name', ['name' => $payment->patient->name])"
                icon="currency"
                iconTone="teal"
            >
                <x-slot name="actions">
                    <a
                        href="{{ route('payments.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        <x-ui.icon name="arrow-left" class="h-4 w-4 shrink-0" />
                        {{ __('Financeiro') }}
                    </a>
                </x-slot>
            </x-page-hero>

            <div class="grid gap-6 lg:grid-cols-12 lg:items-start lg:gap-8">
                {{-- Coluna principal --}}
                <div class="space-y-6 lg:col-span-7 xl:col-span-8">
                    {{-- Hero valor --}}
                    <section
                        class="relative overflow-hidden rounded-3xl border p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 sm:p-8 {{ $heroSurface }}"
                        aria-label="{{ __('Resumo do pagamento') }}"
                    >
                        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/40 blur-3xl dark:bg-white/5" aria-hidden="true"></div>

                        <div class="relative">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge :variant="$badgeVariant">{{ $payment->status->label() }}</x-ui.badge>
                                @if ($payment->payment_method)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $methodChip }}">
                                        {{ $payment->payment_method->label() }}
                                    </span>
                                @endif
                            </div>

                            <p class="mt-4 text-sm font-medium {{ $heroMuted }}">{{ __('Valor da cobrança') }}</p>
                            <p class="mt-1 text-4xl font-extrabold tracking-tight sm:text-5xl {{ $heroAccent }}">
                                R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}
                            </p>

                            @if ($payment->platform_fee !== null && $payment->professional_amount !== null)
                                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                                    <div class="rounded-xl px-4 py-3 ring-1 {{ $heroSubcard }}">
                                        <p class="text-[10px] font-bold uppercase tracking-wider {{ $heroMuted }}">{{ __('Profissional') }}</p>
                                        <p class="mt-1 text-lg font-bold {{ $heroAccent }}">R$ {{ number_format((float) $payment->professional_amount, 2, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-xl px-4 py-3 ring-1 {{ $heroSubcard }}">
                                        <p class="text-[10px] font-bold uppercase tracking-wider {{ $heroMuted }}">{{ __('Plataforma') }}</p>
                                        <p class="mt-1 text-lg font-bold {{ $heroAccent }}">R$ {{ number_format((float) $payment->platform_fee, 2, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- Vínculos --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
                        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Vínculos') }}</h2>
                        </div>
                        <dl class="divide-y divide-slate-100 dark:divide-slate-800">
                            <div class="flex items-center gap-4 px-5 py-4">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 text-sm font-bold text-white shadow-sm" aria-hidden="true">{{ $patientInitial }}</span>
                                <div class="min-w-0 flex-1">
                                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Cobrado a') }}</dt>
                                    <dd class="mt-0.5 truncate">
                                        <a href="{{ route('patients.show', $payment->patient) }}" class="text-sm font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400">
                                            {{ $payment->patient->name }}
                                        </a>
                                    </dd>
                                </div>
                                <a href="{{ route('patients.show', $payment->patient) }}" class="shrink-0 text-xs font-semibold text-slate-500 hover:text-violet-600 dark:hover:text-violet-400">{{ __('Ver ficha') }} →</a>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div>
                                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Sessão associada') }}</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-slate-800 dark:text-slate-100">
                                        @if ($payment->therapySession)
                                            <a href="{{ route('therapy-sessions.show', $payment->therapySession) }}" class="text-violet-600 hover:text-violet-500 dark:text-violet-400">
                                                {{ $payment->therapySession->session_date->format('d/m/Y') }}
                                                · {{ \Illuminate\Support\Str::of($payment->therapySession->session_time)->substr(0, 5) }}
                                            </a>
                                        @else
                                            <span class="text-slate-500 dark:text-slate-400">{{ __('Nenhuma sessão vinculada') }}</span>
                                        @endif
                                    </dd>
                                </div>
                                @if ($payment->therapySession)
                                    <a href="{{ route('therapy-sessions.show', $payment->therapySession) }}" class="shrink-0 text-xs font-semibold text-slate-500 hover:text-violet-600 dark:hover:text-violet-400">{{ __('Abrir') }} →</a>
                                @endif
                            </div>
                        </dl>
                    </section>

                    {{-- Histórico --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
                        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Histórico') }}</h2>
                        </div>
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800" role="list">
                            <li class="flex items-start gap-3 px-5 py-4">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                    <x-ui.icon name="calendar" class="h-4 w-4" />
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('Registado') }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $payment->created_at->format('d/m/Y · H:i') }}</p>
                                </div>
                            </li>
                            @if ($payment->paid_at)
                                <li class="flex items-start gap-3 px-5 py-4">
                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">
                                        <x-ui.icon name="check-badge" class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('Pago') }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $payment->paid_at->format('d/m/Y · H:i') }}</p>
                                    </div>
                                </li>
                            @endif
                            @if ($payment->updated_at && $payment->updated_at->ne($payment->created_at))
                                <li class="flex items-start gap-3 px-5 py-4">
                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-400">
                                        <x-ui.icon name="pencil" class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('Última atualização') }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $payment->updated_at->format('d/m/Y · H:i') }}</p>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </section>

                    {{-- Observações --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                            <div>
                                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Observações') }}</h2>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Notas internas — só visíveis para si.') }}</p>
                            </div>
                            <a href="{{ route('payments.edit', $payment) }}" class="text-xs font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400">{{ __('Editar') }}</a>
                        </div>
                        <div class="p-5">
                            @if ($payment->notes)
                                <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800 dark:text-slate-100">{{ $payment->notes }}</p>
                            @else
                                <p class="text-sm italic text-slate-400 dark:text-slate-500">{{ __('Sem observações registadas.') }}</p>
                            @endif
                        </div>
                    </section>
                </div>

                {{-- Barra lateral: ações --}}
                <aside class="space-y-4 lg:col-span-5 xl:col-span-4">
                    @if ($payment->status === \App\Enums\PaymentStatus::PendingConfirmation)
                        @can('confirmManual', $payment)
                            <section class="overflow-hidden rounded-2xl border border-sky-200/80 bg-sky-50/60 p-5 dark:border-sky-800/40 dark:bg-sky-950/30">
                                <h2 class="text-xs font-bold uppercase tracking-wider text-sky-900 dark:text-sky-200">{{ __('PIX manual') }}</h2>
                                <p class="mt-2 text-sm text-sky-950/90 dark:text-sky-100/90">
                                    {{ __('O paciente indicou que já pagou. Confirme o recebimento para marcar como pago.') }}
                                </p>
                                <form method="post" action="{{ route('payments.confirm-manual', $payment) }}" class="mt-4">
                                    @csrf
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                                        {{ __('Confirmar pagamento PIX') }}
                                    </button>
                                </form>
                            </section>
                        @endcan
                    @endif

                    @include('payments.partials.quick-update-actions', ['payment' => $payment])

                    <section class="overflow-hidden rounded-2xl border border-rose-200/70 bg-rose-50/40 dark:border-rose-900/40 dark:bg-rose-950/20">
                        <div class="px-5 py-4">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-rose-900 dark:text-rose-200">{{ __('Zona de risco') }}</h2>
                            <p class="mt-1 text-xs leading-relaxed text-rose-800/80 dark:text-rose-200/80">
                                {{ __('Remover este registo é permanente e não pode ser desfeito.') }}
                            </p>
                            <x-confirm-form
                                method="post"
                                action="{{ route('payments.destroy', $payment) }}"
                                :title="__('Remover pagamento?')"
                                :message="__('Este registro financeiro será excluído permanentemente.')"
                                :confirm-label="__('Sim, remover')"
                                variant="danger"
                                :validate="false"
                                class="mt-4 block"
                            >
                                @csrf
                                @method('delete')
                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-sm font-semibold text-rose-800 transition hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200 dark:hover:bg-rose-950/60"
                                >
                                    <x-ui.icon name="trash" class="h-4 w-4 shrink-0" />
                                    {{ __('Excluir pagamento') }}
                                </button>
                            </x-confirm-form>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
