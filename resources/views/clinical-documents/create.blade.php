<x-app-layout>
    <x-slot name="header">{{ $documentType->label() }}</x-slot>

    @php
        $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
        $iconTone = match ($documentType->value) {
            'declaracao' => 'indigo',
            'receita' => 'teal',
            default => 'violet',
        };
        $documentsTabUrl = route('patients.show', ['patient' => $patient, 'tab' => 'document-requests']);
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8" data-test="clinical-document-create">
            @include('document-requests.partials.patient-breadcrumb-trail', [
                'patient' => $patient,
                'current' => $documentType->label(),
            ])

            <x-page-hero
                :title="$documentType->label()"
                :subtitle="$documentType->description()"
                :icon="$documentType->icon()"
                :iconTone="$iconTone"
            >
                <x-slot name="eyebrow">{{ __('Emitir documento clínico') }}</x-slot>
                <x-slot name="actions">
                    <a
                        href="{{ $documentsTabUrl }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        <x-ui.icon name="arrow-left" class="h-4 w-4" />
                        {{ __('Voltar à ficha') }}
                    </a>
                </x-slot>
            </x-page-hero>

            <x-clinical-documents.type-nav :patient="$patient" :document-type="$documentType" />

            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                <div class="space-y-6 lg:col-span-8">
                    <form
                        method="post"
                        action="{{ route('patients.clinical-documents.store', $patient) }}"
                        class="space-y-6"
                        @if ($documentType->value === 'atestado')
                            x-data="clinicalDocumentForm()"
                            @submit="if (!validateDates()) { $event.preventDefault(); }"
                        @endif
                    >
                        @csrf
                        <input type="hidden" name="type" value="{{ $documentType->value }}" />

                        @if ($errors->any())
                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                                <ul class="list-inside list-disc space-y-1">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @include('clinical-documents._form', [
                            'patient' => $patient,
                            'documentType' => $documentType,
                            'defaultBody' => $defaultBody,
                            'inputBase' => $inputBase,
                        ])

                        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60 sm:flex-row sm:items-center sm:justify-between">
                            <p class="flex items-start gap-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                                <x-ui.icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-violet-500" />
                                {{ __('Use «Pré-visualizar» para conferir o PDF antes de arquivar. Ao gerar, o documento fica salvo na ficha do paciente.') }}
                            </p>
                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:shrink-0">
                                <a
                                    href="{{ $documentsTabUrl }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    {{ __('Cancelar') }}
                                </a>
                                <button
                                    type="submit"
                                    formaction="{{ route('patients.clinical-documents.preview', $patient) }}"
                                    formtarget="_blank"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-5 py-2.5 text-sm font-semibold text-violet-700 transition hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/50 dark:text-violet-300 dark:hover:bg-violet-950"
                                >
                                    <x-ui.icon name="eye" class="h-4 w-4" />
                                    {{ __('Pré-visualizar') }}
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                                >
                                    <x-ui.icon name="download" class="h-4 w-4" />
                                    {{ __('Gerar PDF') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-4">
                    @include('clinical-documents.partials.create-sidebar', [
                        'patient' => $patient,
                        'documentType' => $documentType,
                    ])
                </div>
            </div>
        </div>
    </div>

    @if ($documentType->value === 'atestado')
        @push('scripts')
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('clinicalDocumentForm', () => ({
                        kind: @js(old('atestado_kind', 'comparecimento')),
                        dateError: '',
                        patientName: @js($patient->name),
                        init() {
                            this.$watch('kind', (value) => {
                                if (value === 'afastamento') {
                                    this.$nextTick(() => this.syncEndFromDays());
                                } else {
                                    this.dateError = '';
                                }
                            });
                            this.$nextTick(() => this.validateDates());
                        },
                        addDays(isoDate, days) {
                            const date = new Date(isoDate + 'T12:00:00');
                            date.setDate(date.getDate() + days);
                            return date.toISOString().slice(0, 10);
                        },
                        daysBetween(start, end) {
                            const s = new Date(start + 'T12:00:00');
                            const e = new Date(end + 'T12:00:00');
                            return Math.round((e - s) / 86400000) + 1;
                        },
                        syncEndFromDays() {
                            if (this.kind !== 'afastamento') return;
                            const daysEl = document.getElementById('days');
                            const startEl = document.getElementById('start_date');
                            const endEl = document.getElementById('end_date');
                            const days = Math.max(1, parseInt(daysEl?.value || '1', 10));
                            const start = startEl?.value;
                            if (! start || ! endEl) return;
                            endEl.value = this.addDays(start, days - 1);
                            this.validateDates();
                        },
                        syncDaysFromRange() {
                            if (this.kind !== 'afastamento') return;
                            const startEl = document.getElementById('start_date');
                            const endEl = document.getElementById('end_date');
                            const daysEl = document.getElementById('days');
                            const start = startEl?.value;
                            const end = endEl?.value;
                            if (! start || ! end || ! daysEl) return;
                            if (end >= start) {
                                daysEl.value = this.daysBetween(start, end);
                            }
                            this.validateDates();
                        },
                        validateDates() {
                            if (this.kind !== 'afastamento') {
                                this.dateError = '';
                                return true;
                            }
                            const start = document.getElementById('start_date')?.value;
                            const end = document.getElementById('end_date')?.value;
                            if (! start || ! end) {
                                this.dateError = '';
                                return true;
                            }
                            if (end < start) {
                                this.dateError = @js(__('A data final deve ser igual ou posterior à data inicial.'));
                                return false;
                            }
                            this.dateError = '';
                            return true;
                        },
                        applyTemplate() {
                            const name = this.patientName;
                            if (this.kind === 'afastamento') {
                                const days = document.getElementById('days')?.value || 1;
                                const start = document.getElementById('start_date')?.value;
                                const end = document.getElementById('end_date')?.value;
                                const fmt = (v) => v ? new Date(v + 'T12:00:00').toLocaleDateString('pt-BR') : '';
                                document.getElementById('body').value = `Atesto, para os devidos fins, que o(a) paciente ${name} necessita de afastamento de suas atividades por ${days} dia(s), no período de ${fmt(start)} a ${fmt(end)}, em razão de acompanhamento psicológico.`;
                            } else {
                                const session = document.getElementById('session_date')?.value;
                                const fmt = session ? new Date(session + 'T12:00:00').toLocaleDateString('pt-BR') : new Date().toLocaleDateString('pt-BR');
                                document.getElementById('body').value = `Atesto, para os devidos fins, que o(a) paciente ${name} compareceu a consulta psicológica na data de ${fmt}, no horário regular de atendimento.`;
                            }
                        },
                    }));
                });
            </script>
        @endpush
    @endif
</x-app-layout>
