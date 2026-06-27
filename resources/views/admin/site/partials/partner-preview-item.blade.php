@if ($partner->logoUrl())
    <img
        src="{{ $partner->logoUrl() }}"
        alt="{{ $partner->name }}"
        class="h-10 max-w-[140px] object-contain"
        loading="lazy"
    />
@else
    <span class="text-lg font-bold tracking-tight text-slate-700 dark:text-slate-200">{{ $partner->name }}</span>
@endif
