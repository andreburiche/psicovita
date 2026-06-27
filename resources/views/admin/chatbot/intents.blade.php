@php
    $phraseLines = static fn (array $phrases): string => implode("\n", $phrases);
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Intents do chatbot') }}</x-slot>

    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-hero
            :title="__('Intents do chatbot')"
            :subtitle="__('Configure frases de treino, respostas e encaminhamento para filas. Fluxo: :flow', ['flow' => $flow->name])"
            icon="sparkles"
            iconTone="indigo"
        />

        @if (session('status'))
            <x-ui.success-alert :title="session('status')" />
        @endif

        <div class="flex flex-wrap gap-3 text-sm font-semibold">
            <a href="{{ route('admin.support.metrics') }}" class="text-violet-600 hover:underline">{{ __('Métricas') }}</a>
            <a href="{{ route('admin.support.index') }}" class="text-violet-600 hover:underline">{{ __('Central de suporte') }}</a>
        </div>

        <section class="overflow-hidden rounded-2xl border border-dashed border-indigo-300/80 bg-gradient-to-br from-indigo-50/80 via-white to-violet-50/60 p-6 shadow-sm dark:border-indigo-800/60 dark:from-indigo-950/30 dark:via-slate-900/80 dark:to-slate-900/80">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Novo intent') }}</h2>
            @include('admin.chatbot.partials.intent-form', [
                'action' => route('admin.chatbot.intents.store'),
                'intent' => null,
                'response' => null,
                'queues' => $queues,
                'formId' => 'new-intent',
            ])
        </section>

        <div class="space-y-5">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Intents cadastrados') }} ({{ $intents->count() }})</h2>

            @foreach ($intents as $intent)
                @php
                    $response = $intent->responses->firstWhere('locale', 'pt_BR') ?? $intent->responses->first();
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white">{{ $intent->label }}</h3>
                            <p class="font-mono text-xs text-slate-500">{{ $intent->slug }} · {{ __('Prioridade') }} {{ $intent->priority }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                'bg-emerald-100 text-emerald-800' => $intent->is_active,
                                'bg-slate-200 text-slate-600' => ! $intent->is_active,
                            ])>{{ $intent->is_active ? __('Activo') : __('Inactivo') }}</span>
                            <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold text-violet-800">{{ $intent->route_action }}</span>
                        </div>
                    </div>

                    @include('admin.chatbot.partials.intent-form', [
                        'action' => route('admin.chatbot.intents.update', $intent),
                        'method' => 'patch',
                        'intent' => $intent,
                        'response' => $response,
                        'queues' => $queues,
                        'formId' => 'intent-'.$intent->id,
                    ])

                    <form method="post" action="{{ route('admin.chatbot.intents.destroy', $intent) }}" class="mt-3" onsubmit="return confirm(@js(__('Remover este intent?')))">
                        @csrf
                        @method('delete')
                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">{{ __('Remover') }}</button>
                    </form>
                </article>
            @endforeach
        </div>
    </div>
</x-app-layout>
