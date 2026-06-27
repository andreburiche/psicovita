@props([
    'patient',
    'documentRequest',
])

<section
    class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-slate-900 via-sky-950 to-violet-950 shadow-xl shadow-sky-950/20 ring-1 ring-white/10 dark:border-slate-700/50"
    aria-label="{{ __('Detalhes da solicitação') }}"
>
    <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-sky-500/20 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-12 -left-12 h-40 w-40 rounded-full bg-violet-500/15 blur-3xl" aria-hidden="true"></div>

    <div class="relative p-6 sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-200/90">{{ __('Solicitação de documentos') }}</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $documentRequest->institution_name }}</h1>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 ring-white/20 sm:text-sm {{ $documentRequest->status->badgeClass() }}">
                        {{ $documentRequest->status->label() }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-white/15 backdrop-blur-sm sm:text-sm">
                        <x-ui.icon name="building" class="h-3.5 w-3.5 text-sky-200" />
                        {{ $documentRequest->institution_type->label() }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-white/15 backdrop-blur-sm sm:text-sm">
                        <x-ui.icon name="calendar" class="h-3.5 w-3.5 text-sky-200" />
                        {{ __('Solicitado em') }} {{ $documentRequest->request_date->format('d/m/Y') }}
                    </span>
                </div>
                <p class="mt-4 text-sm text-slate-300/90">
                    {{ __('Paciente') }}:
                    <a href="{{ route('patients.show', $patient) }}" class="font-semibold text-white underline decoration-white/30 underline-offset-2 hover:decoration-white/60">
                        {{ $patient->name }}
                    </a>
                </p>
            </div>

            <div class="flex shrink-0 flex-col gap-3 sm:items-end">
                <div class="grid w-full gap-2 rounded-2xl bg-white/5 p-4 ring-1 ring-white/10 sm:min-w-[14rem]">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-slate-300">{{ __('Previsão de retorno') }}</span>
                        <span class="font-semibold text-white">{{ $documentRequest->expected_return_date?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-slate-300">{{ __('Anexos') }}</span>
                        <span class="font-semibold text-white">{{ $documentRequest->files->count() }}</span>
                    </div>
                    @if ($documentRequest->last_email_sent_at)
                        <div class="border-t border-white/10 pt-2 text-xs text-slate-300">
                            {{ __('E-mail enviado em') }} {{ $documentRequest->last_email_sent_at->format('d/m/Y H:i') }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    @can('downloadPdf', $documentRequest)
                        <a
                            href="{{ route('patients.document-requests.pdf', [$patient, $documentRequest]) }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-sky-900 shadow-md transition hover:bg-sky-50"
                        >
                            <x-ui.icon name="download" class="h-4 w-4" />
                            {{ __('Baixar PDF') }}
                        </a>
                    @endcan
                    @can('update', $documentRequest)
                        <a
                            href="{{ route('patients.document-requests.edit', [$patient, $documentRequest]) }}"
                            class="inline-flex items-center rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/15"
                        >
                            {{ __('Editar') }}
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</section>
