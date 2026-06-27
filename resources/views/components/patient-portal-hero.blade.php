@props([
    'title',
    'subtitle' => null,
    'icon' => 'user',
])

<section
    class="relative overflow-hidden rounded-3xl border border-emerald-200/50 bg-gradient-to-br from-emerald-900 via-teal-950 to-slate-900 p-6 shadow-xl shadow-emerald-950/25 ring-1 ring-white/10 sm:p-8 dark:border-emerald-800/40"
    aria-label="{{ $title }}"
>
    <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-emerald-500/20 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-12 -left-12 h-40 w-40 rounded-full bg-teal-500/15 blur-3xl" aria-hidden="true"></div>

    <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 items-start gap-4">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/20 backdrop-blur-sm" aria-hidden="true">
                <x-ui.icon :name="$icon" class="h-7 w-7" />
            </span>
            <div class="min-w-0">
                <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/90">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</section>
