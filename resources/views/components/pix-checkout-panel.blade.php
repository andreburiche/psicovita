@props([
    'pix' => [],
    'stub' => false,
    'payment' => null,
    'manual' => false,
    'notConfigured' => false,
])

@php
    $encodedImage = $pix['encoded_image'] ?? null;
    $imageUrl = $pix['image_url'] ?? null;
    $imageMime = $pix['image_mime'] ?? 'image/png';
    $payload = $pix['payload'] ?? null;
    $expiration = $pix['expiration_date'] ?? null;
    $bankLabel = $pix['bank_label'] ?? null;
    $isStaticFallback = (bool) ($pix['raw']['static_fallback'] ?? false);
    $isManual = $manual || (bool) ($pix['raw']['manual'] ?? false);
    $hasImage = filled($imageUrl) || filled($encodedImage);
    $imageSrc = filled($imageUrl)
        ? $imageUrl
        : (filled($encodedImage) ? "data:{$imageMime};base64,{$encodedImage}" : null);
    $canShow = $notConfigured || ($hasImage && \App\Support\PixCheckout::isDisplayable($pix)) || ($isManual && (filled($payload) || $hasImage));
@endphp

@if ($notConfigured)
    <section class="rounded-2xl border border-amber-200/80 bg-amber-50/80 p-5 shadow-sm dark:border-amber-800/40 dark:bg-amber-950/30">
        <h2 class="text-sm font-bold uppercase tracking-wider text-amber-900 dark:text-amber-200">{{ __('Recebimento não configurado') }}</h2>
        <p class="mt-2 text-sm text-amber-950/90 dark:text-amber-100/90">
            {{ __('Este profissional ainda não configurou o recebimento. Entre em contacto diretamente para combinar o pagamento.') }}
        </p>
    </section>
@elseif ($canShow)
    <section class="rounded-2xl border border-emerald-200/80 bg-white p-5 shadow-sm dark:border-emerald-800/40 dark:bg-slate-900/80" x-data="{ copied: false }">
        <h2 class="text-sm font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">{{ __('Pagar com PIX') }}</h2>
        @if ($bankLabel)
            <p class="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $bankLabel }}</p>
        @endif
        @if ($isManual)
            <p class="mt-2 text-xs text-sky-800 dark:text-sky-300">
                {{ __('PIX do profissional — escaneie o QR Code ou use a chave/link abaixo. Depois toque em «Já paguei».') }}
            </p>
        @elseif ($stub)
            <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                @if ($isStaticFallback)
                    {{ __('Pagamento manual — escaneie o QR Code abaixo. Configure ASAAS_ENABLED=true para cobranças automáticas.') }}
                @else
                    {{ __('Modo demonstração — configure ASAAS_ENABLED=true para cobranças reais.') }}
                @endif
            </p>
        @endif

        <div class="mt-4 flex flex-col items-center gap-4 sm:flex-row sm:items-start">
            @if ($imageSrc)
                <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-inner dark:border-slate-600">
                    <img
                        src="{{ $imageSrc }}"
                        alt="{{ __('QR Code PIX') }}"
                        class="h-48 w-48 max-w-full object-contain"
                        width="192"
                        height="192"
                    />
                </div>
            @endif
            @if (filled($payload))
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        {{ $isManual ? __('Chave PIX ou link de pagamento:') : __('Copie o código PIX e cole no app do seu banco:') }}
                    </p>
                    <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-mono break-all text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                        {{ e($payload) }}
                    </div>
                    <button
                        type="button"
                        class="mt-3 inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500"
                        x-on:click="navigator.clipboard.writeText(@js($payload)); copied = true; setTimeout(() => copied = false, 2500)"
                    >
                        <span x-show="!copied">{{ __('Copiar') }}</span>
                        <span x-show="copied" x-cloak>{{ __('Copiado!') }}</span>
                    </button>
                    @if ($expiration)
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            {{ __('Validade do QR') }}: {{ \Illuminate\Support\Carbon::parse($expiration)->format('d/m/Y H:i') }}
                        </p>
                    @endif
                </div>
            @elseif ($imageSrc)
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        {{ __('Abra o app do seu banco, escolha pagar com PIX e escaneie o QR Code ao lado.') }}
                    </p>
                </div>
            @endif
        </div>

        @if ($isManual && $payment)
            @can('reportPaid', $payment)
                <form method="post" action="{{ route('patient.payments.already-paid', $payment) }}" class="mt-5 border-t border-emerald-100 pt-4 dark:border-emerald-900/40">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-sky-500 sm:w-auto"
                        onclick="return confirm(@js(__('Confirma que já efectuou o pagamento PIX?')))"
                    >
                        {{ __('Já paguei') }}
                    </button>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('O profissional será notificado para confirmar o recebimento.') }}
                    </p>
                </form>
            @endcan
        @endif
    </section>
@endif
