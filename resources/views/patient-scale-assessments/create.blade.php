<x-app-layout>
    <x-slot name="header">{{ $scaleType->label() }}</x-slot>

    @php
        $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
        $assessmentsTabUrl = route('patients.show', ['patient' => $patient, 'tab' => 'assessments']);
        $iconTone = match ($scaleType->value) {
            'bdi' => 'indigo',
            'stress' => 'teal',
            default => 'violet',
        };
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8" data-test="scale-assessment-create">
            @include('document-requests.partials.patient-breadcrumb-trail', [
                'patient' => $patient,
                'current' => $scaleType->label(),
            ])

            <x-page-hero
                :title="$scaleType->label()"
                :subtitle="$definition['description'] ?? ''"
                :icon="$scaleType->icon()"
                :iconTone="$iconTone"
            >
                <x-slot name="eyebrow">{{ __('Aplicar escala clínica') }}</x-slot>
                <x-slot name="actions">
                    <a
                        href="{{ $assessmentsTabUrl }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        <x-ui.icon name="arrow-left" class="h-4 w-4" />
                        {{ __('Voltar à ficha') }}
                    </a>
                </x-slot>
            </x-page-hero>

            <x-patient-scale-assessments.type-nav :patient="$patient" :scale-type="$scaleType" />

            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-8">
                    <form method="post" action="{{ route('patients.scale-assessments.store', $patient) }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="scale_type" value="{{ $scaleType->value }}" />

                        @if ($errors->any())
                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                                <ul class="list-inside list-disc space-y-1">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @include('patient-scale-assessments._form', [
                            'scaleType' => $scaleType,
                            'definition' => $definition,
                            'questions' => $questions,
                            'options' => $options,
                            'inputBase' => $inputBase,
                        ])

                        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60 sm:flex-row sm:items-center sm:justify-between">
                            <p class="flex items-start gap-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                                <x-ui.icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-violet-500" />
                                {{ __('Instrumento de triagem — interpretação clínica é responsabilidade do profissional. Resultado salvo na aba Avaliações.') }}
                            </p>
                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:shrink-0">
                                <a
                                    href="{{ $assessmentsTabUrl }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    {{ __('Cancelar') }}
                                </a>
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                                >
                                    <x-ui.icon name="check" class="h-4 w-4" />
                                    {{ __('Salvar avaliação') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-4">
                    @include('patient-scale-assessments.partials.create-sidebar', [
                        'patient' => $patient,
                        'scaleType' => $scaleType,
                        'definition' => $definition,
                        'latestForScale' => $latestForScale ?? null,
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
