@props([
    'title',
    'subtitle' => null,
    'badge' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80']) }}>
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">{{ $title }}</h3>
            @if ($subtitle)
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $subtitle }}</p>
            @endif
        </div>
        @if ($badge)
            <span class="inline-flex shrink-0 items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-200">{{ $badge }}</span>
        @endif
    </div>
    <div class="space-y-4">
        {{ $slot }}
    </div>
</div>
