@props(['patient', 'clinicalDocuments'])

@can('viewAny', [\App\Models\PatientClinicalDocument::class, $patient])
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900/80">
        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-4 dark:border-slate-700 dark:from-slate-900">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ __('Documentos emitidos') }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ __('Atestados, declarações e receitas gerados nesta ficha.') }}</p>
        </div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-700">
            @forelse ($clinicalDocuments as $doc)
                <li class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $doc->type->label() }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ __('Emitido em') }} {{ $doc->issued_at->format('d/m/Y') }}
                            · {{ $doc->created_at->format('d/m/Y H:i') }}
                            @if ($doc->professional)
                                · {{ $doc->professional->name }}
                            @endif
                        </p>
                    </div>
                    <a
                        href="{{ route('patients.clinical-documents.pdf', [$patient, $doc]) }}"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-semibold text-violet-700 transition hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-300"
                    >
                        <x-ui.icon name="download" class="h-4 w-4" />
                        {{ __('Baixar PDF') }}
                    </a>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">{{ __('Nenhum documento clínico emitido ainda.') }}</li>
            @endforelse
        </ul>
    </div>
@endcan
