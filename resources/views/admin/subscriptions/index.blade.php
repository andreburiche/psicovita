<x-app-layout>
    <x-slot name="header">{{ __('Assinaturas dos profissionais') }}</x-slot>

    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <x-page-hero
                :title="__('Planos PsiConecta — controle administrativo')"
                :subtitle="__('Status de pagamento, renovações e validade das assinaturas de cada profissional titular.')"
                icon="currency"
                iconTone="emerald"
            />
            <a href="{{ route('admin.site.plans') }}" class="shrink-0 text-sm font-semibold text-violet-600 hover:underline dark:text-violet-400">{{ __('Editar catálogo de planos') }}</a>
        </div>

        @if ($manualActivationEnabled)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                {{ __('Modo manual activo: confirme pagamentos em «Validar pagamento» até Asaas ou Mercado Pago estarem configurados.') }}
            </div>
        @endif

        @php
            $totalSubscriptions = array_sum($summary);
            $activeFilter = (string) ($filters['status'] ?? '');
            $filterBaseQuery = array_filter([
                'q' => $filters['q'] ?? null,
                'plan_id' => $filters['plan_id'] ?? null,
            ], fn ($value) => filled($value));
            $statusMeta = [
                'trialing' => ['icon' => 'clock', 'tone' => 'sky'],
                'active' => ['icon' => 'check-badge', 'tone' => 'emerald'],
                'past_due' => ['icon' => 'alert-triangle', 'tone' => 'amber'],
                'cancelled' => ['icon' => 'x', 'tone' => 'slate'],
                'expired' => ['icon' => 'alert-circle', 'tone' => 'rose'],
            ];
        @endphp

        <section aria-labelledby="subscription-stats-heading" class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="subscription-stats-heading" class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Resumo por status') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Clique num card para filtrar a lista abaixo.') }}</p>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    <span class="font-bold tabular-nums text-slate-900 dark:text-white">{{ $totalSubscriptions }}</span>
                    {{ trans_choice('profissional com assinatura|profissionais com assinatura', $totalSubscriptions) }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <x-admin.subscription-status-stat
                    :label="__('Todos')"
                    :count="$totalSubscriptions"
                    icon="users"
                    tone="violet"
                    :active="$activeFilter === ''"
                    :href="route('admin.subscriptions.index', $filterBaseQuery)"
                />

                @foreach ($statuses as $value => $label)
                    @php
                        $meta = $statusMeta[$value] ?? ['icon' => 'banknote', 'tone' => 'violet'];
                    @endphp
                    <x-admin.subscription-status-stat
                        :label="$label"
                        :count="$summary[$value] ?? 0"
                        :icon="$meta['icon']"
                        :tone="$meta['tone']"
                        :active="$activeFilter === $value"
                        :href="route('admin.subscriptions.index', array_merge($filterBaseQuery, ['status' => $value]))"
                    />
                @endforeach
            </div>
        </section>

        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <div class="min-w-[10rem] flex-1">
                <x-input-label for="q" :value="__('Buscar profissional')" />
                <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Nome ou e-mail…') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" />
            </div>
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('Todos') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="plan_id" :value="__('Plano')" />
                <select id="plan_id" name="plan_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('Todos') }}</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected((string) ($filters['plan_id'] ?? '') === (string) $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/80">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Profissional') }}</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Plano') }}</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Pagamento') }}</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Validade') }}</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">{{ __('Última renovação') }}</th>
                        @if ($manualActivationEnabled)
                            <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-300">{{ __('Ações') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($subscriptions as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900 dark:text-white">{{ $item->user?->name }}</p>
                                <p class="text-xs text-slate-500">{{ $item->user?->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $item->plan?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span @class(['inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', $item->status->badgeClass()])>
                                    {{ $item->status->label() }}
                                </span>
                                @if ($item->cancelled_at)
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Cancelada em :date', ['date' => $item->cancelled_at->format('d/m/Y')]) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                                @if ($item->paymentMethodLabel() || $item->billingCycleLabel())
                                    <p>{{ collect([$item->paymentMethodLabel(), $item->billingCycleLabel()])->filter()->implode(' · ') }}</p>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                                @if ($item->isManuallyValidated())
                                    <p class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                                        {{ __('Validado manualmente') }}
                                        @if ($item->manualValidatorLabel())
                                            · {{ $item->manualValidatorLabel() }}
                                        @endif
                                    </p>
                                @elseif (filled($item->gateway_external_id))
                                    <p class="mt-1 text-xs text-slate-400">{{ __('Asaas') }}: {{ Str::limit($item->gateway_external_id, 16) }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-400">
                                {{ $item->expirationDate()?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-400">
                                {{ $item->lastRenewalAt()?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            @if ($manualActivationEnabled)
                                <td class="px-4 py-3">
                                    <div class="flex justify-end">
                                        <x-admin.subscription-validate-action :subscription="$item" />
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $manualActivationEnabled ? 7 : 6 }}" class="px-4 py-10 text-center text-slate-500">{{ __('Nenhuma assinatura encontrada.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $subscriptions->links() }}

        <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ __('Com gateway activo, pagamentos são confirmados automaticamente pelo Asaas. Enquanto isso, use a validação manual e registe observações (ex.: comprovante PIX).') }}
        </p>
    </div>
</x-app-layout>
