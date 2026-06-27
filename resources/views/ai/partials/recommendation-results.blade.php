@props([
    'rows' => [],
])

<ul class="space-y-4">
    @foreach ($rows as $i => $row)
        @if (! is_array($row))
            @continue
        @endif
        @php
            $compatibility = (int) ($row['compatibility'] ?? 0);
            $compatibility = max(0, min(100, $compatibility));
        @endphp
        <li class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100/80 dark:border-slate-600 dark:bg-slate-900/80 dark:ring-slate-700/50">
            <div class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-indigo-500 to-violet-600" aria-hidden="true"></div>
            <div class="flex flex-wrap items-start gap-4 pl-2">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-600/25">
                    {{ $i + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-base font-bold text-slate-900 dark:text-white">{{ $row['name'] ?? __('Profissional') }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ $row['specialty'] ?? '—' }}
                                @if (! empty($row['approach']))
                                    · {{ $row['approach'] }}
                                @endif
                            </p>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold tabular-nums text-indigo-700 ring-1 ring-indigo-200/80 dark:bg-indigo-950/60 dark:text-indigo-200 dark:ring-indigo-800/50">
                            {{ $compatibility }}% {{ __('compat.') }}
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-center justify-between gap-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                            <span>{{ __('Afinidade ilustrativa') }}</span>
                            <span class="tabular-nums text-indigo-600 dark:text-indigo-400">{{ $compatibility }}%</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all"
                                style="width: {{ $compatibility }}%"
                            ></div>
                        </div>
                    </div>
                    @if (! empty($row['justification']))
                        <p class="mt-4 text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ $row['justification'] }}</p>
                    @endif
                </div>
            </div>
        </li>
    @endforeach
</ul>
