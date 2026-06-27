@props([
    'title',
    'message',
    'href' => null,
    'linkLabel' => null,
])

<section class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/80 px-6 py-12 text-center dark:border-slate-600 dark:bg-slate-900/40">
    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200/80 text-slate-600 dark:bg-slate-800 dark:text-slate-300" aria-hidden="true">
        <x-ui.icon name="lock-closed" class="h-6 w-6" />
    </span>
    <p class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</p>
    <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $message }}</p>
    @if ($href && $linkLabel)
        <a
            href="{{ $href }}"
            class="mt-5 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-violet-200 hover:text-violet-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-violet-700"
        >
            {{ $linkLabel }}
        </a>
    @endif
</section>
