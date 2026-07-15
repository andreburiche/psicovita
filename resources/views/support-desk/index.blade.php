@php
    $initials = static function (string $name): string {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(
            mb_substr($parts[0] ?? '?', 0, 1).
            mb_substr($parts[1] ?? '', 0, 1)
        );
    };

    $slaBadge = static function (string $state): string {
        return match ($state) {
            'breached' => 'bg-rose-100 text-rose-800 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-200',
            'warning' => 'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-200',
            'pending' => 'bg-sky-100 text-sky-800 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-200',
            default => 'bg-emerald-100 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200',
        };
    };

    $filterParams = array_filter([
        'queue' => $filters['queue'] ?? null,
        'status' => $filters['status'] ?? null,
        'mine' => ($filters['mine'] ?? false) ? 1 : null,
        'q' => ($filters['q'] ?? '') !== '' ? $filters['q'] : null,
    ]);
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Central de suporte') }}</x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pb-8 sm:px-6 lg:px-8">
        <x-page-hero
            :title="__('Central de suporte')"
            :subtitle="__('Atendimento humano — filas, protocolos e SLA.')"
            icon="messages"
        />

        <div class="flex flex-wrap items-center gap-3">
            @if ($pendingCount > 0)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-100">
                    {{ __(':count aguardando atendente', ['count' => $pendingCount]) }}
                </span>
            @endif
        </div>

        @include('support-desk.partials.desk-layout')
    </div>
</x-app-layout>
