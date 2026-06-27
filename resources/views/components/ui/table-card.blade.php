<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/70 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-violet-900/40']) }}>
    <div class="-mx-px overflow-x-auto">
        {{ $slot }}
    </div>
</div>
