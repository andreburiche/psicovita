@props(['patient', 'documentType'])

@php
    use App\Enums\PatientClinicalDocumentType;
@endphp

<div class="space-y-2">
    <p class="px-1 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Tipo de documento') }}</p>
    <nav class="flex flex-col gap-2 sm:flex-row sm:flex-wrap" aria-label="{{ __('Tipo de documento') }}">
        @foreach (PatientClinicalDocumentType::cases() as $type)
            @php
                $isActive = $type === $documentType;
                $href = route('patients.clinical-documents.create', [$patient, $type->value]);
                $activeRing = match ($type) {
                    PatientClinicalDocumentType::Declaracao => 'ring-indigo-500/30 border-indigo-300 dark:border-indigo-600',
                    PatientClinicalDocumentType::Receita => 'ring-teal-500/30 border-teal-300 dark:border-teal-600',
                    default => 'ring-violet-500/30 border-violet-300 dark:border-violet-600',
                };
            @endphp
            <a
                href="{{ $href }}"
                @if ($isActive) aria-current="page" @endif
                @class([
                    'group inline-flex min-w-0 flex-1 items-center gap-3 rounded-2xl border bg-white px-4 py-3 shadow-sm transition sm:min-w-[10rem] sm:flex-none',
                    'border-slate-200/90 ring-2 '.$activeRing.' dark:bg-slate-900/80' => $isActive,
                    'border-slate-200/90 hover:border-slate-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/60 dark:hover:border-slate-600' => ! $isActive,
                ])
            >
                <span @class([
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition',
                    'bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-md' => $isActive && $type === PatientClinicalDocumentType::Atestado,
                    'bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-md' => $isActive && $type === PatientClinicalDocumentType::Declaracao,
                    'bg-gradient-to-br from-teal-600 to-emerald-600 text-white shadow-md' => $isActive && $type === PatientClinicalDocumentType::Receita,
                    'bg-slate-100 text-slate-500 group-hover:bg-violet-100 group-hover:text-violet-600 dark:bg-slate-800 dark:text-slate-400 dark:group-hover:bg-violet-950 dark:group-hover:text-violet-400' => ! $isActive,
                ])>
                    <x-ui.icon :name="$type->icon()" class="h-5 w-5" />
                </span>
                <span class="min-w-0 text-left">
                    <span @class([
                        'block truncate text-sm font-bold',
                        'text-slate-900 dark:text-white' => $isActive,
                        'text-slate-700 group-hover:text-slate-900 dark:text-slate-300 dark:group-hover:text-white' => ! $isActive,
                    ])>{{ $type->label() }}</span>
                    <span class="mt-0.5 hidden text-[11px] leading-snug text-slate-500 sm:block dark:text-slate-400">{{ Str::limit($type->description(), 42) }}</span>
                </span>
            </a>
        @endforeach
    </nav>
</div>
