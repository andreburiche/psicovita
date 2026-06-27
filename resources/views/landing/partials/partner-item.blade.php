@if ($partner->logoUrl())
    <img
        src="{{ $partner->logoUrl() }}"
        alt="{{ $partner->name }}"
        class="h-10 max-w-[160px] object-contain sm:h-12"
        loading="lazy"
    />
@else
    <span class="text-xl font-bold tracking-tight text-slate-700 dark:text-slate-200">{{ $partner->name }}</span>
@endif
