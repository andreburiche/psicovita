@props(['patient'])

<a
    href="{{ route('patients.show', $patient) }}"
    class="group flex items-center gap-4 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100 transition hover:border-violet-200 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50 dark:hover:border-violet-700"
>
    <x-patient-avatar :patient="$patient" size="md" class="shrink-0 ring-2 ring-violet-100 dark:ring-violet-900/50" />
    <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Editando ficha de') }}</p>
        <p class="truncate text-lg font-bold text-slate-900 group-hover:text-violet-700 dark:text-white dark:group-hover:text-violet-300">{{ $patient->name }}</p>
    </div>
    <span class="hidden shrink-0 items-center gap-1 text-sm font-semibold text-violet-600 sm:inline-flex dark:text-violet-400">
        {{ __('Ver ficha') }}
        <x-ui.icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
    </span>
</a>
