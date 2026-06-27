<x-app-layout>
    <x-slot name="header">{{ __('Integração WhatsApp') }}</x-slot>

    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-hero
            :title="__('WhatsApp Business')"
            :subtitle="__('Estado da integração configurada via .env — sync de conversas terapêuticas.')"
            icon="globe"
            iconTone="emerald"
        />

        @if (session('connection_ok'))
            <x-ui.success-alert :title="session('connection_ok')" />
        @endif

        @if (session('connection_error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200">
                {{ session('connection_error') }}
            </div>
        @endif

        @if (session('connection_details'))
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300">
                <pre class="whitespace-pre-wrap font-mono">{{ json_encode(session('connection_details'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

        @if ($driver === 'evolution' && ($evolutionApiUrl ?? '') === ($appUrl ?? ''))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
                <p class="font-semibold">{{ __('Configuração incorrecta') }}</p>
                <p class="mt-1">{{ __('EVOLUTION_API_URL não pode ser igual ao APP_URL. O PsiConecta usa a porta 8080; a Evolution deve usar outra (ex.: 8082).') }}</p>
            </div>
        @endif

        @if ($driver === 'evolution')
            <section class="rounded-2xl border border-sky-200/80 bg-white p-6 shadow-sm dark:border-sky-900/40 dark:bg-slate-900/80">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Evolution API (servidor WhatsApp)') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('Não confunda com o PsiConecta nem com apps Expo na porta 8081.') }}</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">EVOLUTION_API_URL</dt>
                        <dd class="mt-1 font-mono text-xs text-slate-800 dark:text-slate-200">{{ $evolutionApiUrl }}</dd>
                    </div>
                    @if ($evolutionManagerUrl)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Painel / QR code') }}</dt>
                            <dd class="mt-1">
                                <a href="{{ $evolutionManagerUrl }}" target="_blank" rel="noopener" class="font-mono text-xs text-sky-600 hover:underline dark:text-sky-400">{{ $evolutionManagerUrl }}</a>
                            </dd>
                        </div>
                    @endif
                </dl>
                <p class="mt-4 text-xs text-slate-500">
                    {{ __('1. Instale Docker Desktop') }} →
                    <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">docker compose up -d evolution</code>
                    {{ __('na pasta do projeto.') }}
                </p>
            </section>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Estado') }}</h2>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Integração') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">
                        @if ($enabled)
                            <span class="text-emerald-600 dark:text-emerald-400">{{ __('Activada') }}</span>
                        @else
                            <span class="text-amber-600 dark:text-amber-400">{{ __('Desactivada') }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Driver') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $driverLabel }} ({{ $driver }})</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Credenciais') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">
                        @if ($configured)
                            <span class="text-emerald-600 dark:text-emerald-400">{{ __('Configuradas') }}</span>
                        @else
                            <span class="text-rose-600 dark:text-rose-400">{{ __('Incompletas — verifique .env') }}</span>
                        @endif
                    </dd>
                </div>
                @if ($driver === 'evolution' && $evolutionInstance)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Instância') }}</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-900 dark:text-white">{{ $evolutionInstance }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Webhook') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('Configure este URL na Evolution ou Meta Business.') }}</p>
            <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-200">
                {{ $webhookUrl }}
            </div>
            @if ($driver === 'evolution' && ! empty($webhookDeliveryUrl) && $webhookDeliveryUrl !== $webhookUrl)
                <p class="mt-3 text-xs font-semibold text-amber-700 dark:text-amber-300">{{ __('URL para a Evolution (Docker) chamar o PsiConecta:') }}</p>
                <div class="mt-1 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 font-mono text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                    {{ $webhookDeliveryUrl }}
                </div>
            @endif
            @if ($driver === 'evolution')
                <form method="post" action="{{ route('admin.integrations.whatsapp.webhook-sync') }}" class="mt-3">
                    @csrf
                    <x-primary-button type="submit" :disabled="! $configured">
                        {{ __('Sincronizar webhook na Evolution') }}
                    </x-primary-button>
                </form>
            @endif
            @if ($driver === 'evolution')
                <p class="mt-2 text-xs text-slate-500">
                    {{ __('Evento:') }} <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">MESSAGES_UPSERT</code>
                    @if ($hasWebhookToken)
                        · {{ __('Token de webhook configurado') }}
                    @endif
                </p>
            @endif
        </section>

        <section class="rounded-2xl border border-emerald-200/80 bg-white p-6 shadow-sm dark:border-emerald-900/40 dark:bg-slate-900/80">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Testar conexão') }}</h2>
            <p class="mt-1 text-xs text-slate-500">
                @if ($driver === 'evolution')
                    {{ __('Verifica se a instância Evolution está conectada (estado open).') }}
                @else
                    {{ __('Consulta o número de telefone na Graph API da Meta.') }}
                @endif
            </p>
            <form method="post" action="{{ route('admin.integrations.whatsapp.test') }}" class="mt-4">
                @csrf
                <x-primary-button type="submit" :disabled="! $configured">
                    {{ __('Testar agora') }}
                </x-primary-button>
            </form>
            <p class="mt-3 text-xs text-slate-500">
                {{ __('Variáveis no .env:') }}
                <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">WHATSAPP_ENABLED</code>,
                <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">WHATSAPP_DRIVER</code>
                @if ($driver === 'evolution')
                    , <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">EVOLUTION_*</code>
                @else
                    , <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">WHATSAPP_ACCESS_TOKEN</code>
                @endif
            </p>
        </section>
    </div>
</x-app-layout>
