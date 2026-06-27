@props(['patient'])

@can('create', [\App\Models\PatientClinicalDocument::class, $patient])
    <section class="overflow-hidden rounded-2xl border border-violet-200/90 bg-white shadow-lg ring-1 ring-violet-100 dark:border-violet-900/50 dark:bg-slate-900/80 dark:ring-violet-950" data-test="clinical-documents-generate">
        <div class="border-b border-violet-100 bg-gradient-to-r from-violet-50/90 to-indigo-50/50 px-5 py-4 dark:border-violet-900/40 dark:from-violet-950/40 dark:to-indigo-950/20">
            <h3 class="text-xs font-bold uppercase tracking-wider text-violet-900 dark:text-violet-200">{{ __('Emitir documentos clínicos') }}</h3>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('Gere atestado, declaração ou receita em PDF a partir da ficha do paciente.') }}</p>
        </div>

        <div class="grid gap-4 p-5 sm:grid-cols-3">
            @foreach (\App\Enums\PatientClinicalDocumentType::cases() as $type)
                <a
                    href="{{ route('patients.clinical-documents.create', [$patient, $type->value]) }}"
                    class="group flex flex-col rounded-2xl border border-slate-200/90 bg-slate-50/50 p-4 transition hover:-translate-y-0.5 hover:border-violet-300 hover:bg-violet-50/60 hover:shadow-md dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-violet-700 dark:hover:bg-violet-950/30"
                >
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm ring-1 ring-violet-100 transition group-hover:bg-violet-600 group-hover:text-white dark:bg-slate-900 dark:ring-violet-900/50">
                        <x-ui.icon :name="$type->icon()" class="h-5 w-5" />
                    </span>
                    <span class="mt-3 text-sm font-bold text-slate-900 dark:text-white">{{ $type->label() }}</span>
                    <span class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $type->description() }}</span>
                    <span class="mt-3 text-xs font-semibold text-violet-600 opacity-0 transition group-hover:opacity-100 dark:text-violet-400">{{ __('Preencher e gerar') }} →</span>
                </a>
            @endforeach
        </div>
    </section>
@endcan
