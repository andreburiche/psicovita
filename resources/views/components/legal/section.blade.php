@props([
    'id',
    'title',
    'number' => null,
])

<section {{ $attributes->merge(['class' => 'scroll-mt-28 border-b border-slate-100 pb-8 last:border-b-0 last:pb-0 dark:border-slate-800']) }} id="{{ $id }}" aria-labelledby="{{ $id }}-heading">
    <h2 id="{{ $id }}-heading" class="flex items-start gap-3 text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl">
        @if ($number !== null)
            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-sm font-bold tabular-nums text-violet-700 dark:bg-violet-950/60 dark:text-violet-300" aria-hidden="true">
                {{ $number }}
            </span>
        @endif
        <span class="min-w-0 pt-0.5">{{ $title }}</span>
    </h2>
    <div class="mt-4 space-y-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300 [&_a]:font-semibold [&_a]:text-violet-600 [&_a]:underline-offset-2 hover:[&_a]:underline dark:[&_a]:text-violet-400 [&_li]:leading-relaxed [&_strong]:font-semibold [&_strong]:text-slate-800 dark:[&_strong]:text-slate-100 [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-5">
        {{ $slot }}
    </div>
</section>
