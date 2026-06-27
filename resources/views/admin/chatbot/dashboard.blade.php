@php
    $statusLabels = collect(\App\Enums\SupportConversationStatus::cases())
        ->mapWithKeys(fn ($case) => [$case->value => $case->label()]);
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Métricas do chatbot') }}</x-slot>

    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-hero
            :title="__('Métricas do chatbot')"
            :subtitle="__('Visão gerencial do atendimento automático e humano nos últimos :days dias.', ['days' => $days])"
            icon="bar-chart-3"
            iconTone="violet"
        />

        @if (session('status'))
            <x-ui.success-alert :title="session('status')" />
        @endif

        <form method="get" class="flex flex-wrap items-end gap-3">
            <div>
                <x-input-label for="days" :value="__('Período (dias)')" />
                <select id="days" name="days" class="mt-1 rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900">
                    @foreach ([7, 14, 30, 60, 90] as $option)
                        <option value="{{ $option }}" @selected($days === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button>{{ __('Actualizar') }}</x-primary-button>
            <a href="{{ route('admin.support.index') }}" class="text-sm font-semibold text-violet-600 hover:underline">{{ __('Ir para central de suporte') }}</a>
            <a href="{{ route('admin.chatbot.intents.index') }}" class="text-sm font-semibold text-violet-600 hover:underline">{{ __('Gerir intents') }}</a>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Conversas no período') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $snapshot['total_conversations'] }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200/80 bg-amber-50/50 p-4 shadow-sm dark:border-amber-900/40 dark:bg-amber-950/20">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">{{ __('Aguardam atendente') }}</p>
                <p class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-200">{{ $snapshot['pending_human'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/20">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">{{ __('Resolvidas no período') }}</p>
                <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-200">{{ $snapshot['resolved_period'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200/80 bg-rose-50/50 p-4 shadow-sm dark:border-rose-900/40 dark:bg-rose-950/20">
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-700">{{ __('Violações SLA') }}</p>
                <p class="mt-1 text-2xl font-bold text-rose-800 dark:text-rose-200">{{ $snapshot['sla_breaches'] }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Desempenho') }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Tempo médio 1.ª resposta') }}</dt>
                        <dd class="font-semibold text-slate-900 dark:text-slate-100">
                            {{ $snapshot['avg_first_response_minutes'] !== null ? $snapshot['avg_first_response_minutes'].' min' : '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Em atendimento agora') }}</dt>
                        <dd class="font-semibold">{{ $snapshot['assigned'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Handoffs para humano') }}</dt>
                        <dd class="font-semibold">{{ $snapshot['handoffs'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Match por frases') }}</dt>
                        <dd class="font-semibold">{{ $snapshot['phrase_matches'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Classificação IA') }}</dt>
                        <dd class="font-semibold">{{ $snapshot['ai_matches'] }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Por canal') }}</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse ($snapshot['by_channel'] as $channel => $total)
                        <li class="flex justify-between gap-4">
                            <span class="text-slate-600 dark:text-slate-300">{{ $channel }}</span>
                            <span class="font-semibold">{{ $total }}</span>
                        </li>
                    @empty
                        <li class="text-slate-500">{{ __('Sem dados no período.') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Por fila') }}</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($snapshot['by_queue'] as $queue)
                        <li class="flex justify-between gap-4">
                            <span>{{ $queue->name }}</span>
                            <span class="font-semibold">{{ $queue->total }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Top intents') }}</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse ($snapshot['top_intents'] as $row)
                        <li class="flex justify-between gap-4">
                            <span class="font-mono text-xs text-violet-700 dark:text-violet-300">{{ $row->intent }}</span>
                            <span class="font-semibold">{{ $row->total }}</span>
                        </li>
                    @empty
                        <li class="text-slate-500">{{ __('Sem intents registados.') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Por estado') }}</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($snapshot['by_status'] as $status => $total)
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        {{ $statusLabels[$status] ?? $status }}: {{ $total }}
                    </span>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
