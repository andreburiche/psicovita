@props([
    'patient',
    'anamnesisForms',
    'selectedForm',
    'anamnesisAnswers' => [],
])

@php
    $answers = is_array($anamnesisAnswers) ? $anamnesisAnswers : [];
    $filledCount = collect($answers)->filter(fn ($v) => filled($v))->count();
    $questionCount = $selectedForm?->questions->count() ?? 0;
@endphp

<div class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
    <div class="flex flex-col gap-3 border-b border-slate-100 bg-gradient-to-r from-violet-50/90 to-indigo-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:from-violet-950/50 dark:to-indigo-950/40">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-violet-900 dark:text-violet-200">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-600/10 text-violet-600 dark:bg-violet-400/15 dark:text-violet-300" aria-hidden="true">
                        <x-ui.icon name="clipboard" class="h-4 w-4" />
                    </span>
                    {{ __('Anamnese') }}
                </h3>
                @if ($questionCount > 0)
                    <span class="inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-700 dark:bg-violet-950 dark:text-violet-300">
                        {{ $filledCount }}/{{ $questionCount }} {{ __('preenchidos') }}
                    </span>
                @endif
            </div>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('Respostas por modelo. Dados encriptados.') }}</p>
        </div>
        <a
            href="{{ route('anamnesis-forms.index') }}"
            class="inline-flex shrink-0 items-center gap-1 rounded-xl border border-violet-200/80 bg-white px-3.5 py-2 text-xs font-semibold text-violet-700 transition hover:border-violet-300 hover:bg-violet-50 dark:border-violet-800 dark:bg-slate-800 dark:text-violet-300 dark:hover:bg-violet-950/40"
        >{{ __('Gerir modelos') }} →</a>
    </div>

    <div class="flex-1 p-5 sm:p-6">
        @if ($anamnesisForms->isEmpty())
            <div class="flex flex-col items-center rounded-xl border border-dashed border-violet-200/70 bg-violet-50/30 px-6 py-12 text-center dark:border-violet-900/50 dark:bg-violet-950/15">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400" aria-hidden="true">
                    <x-ui.icon name="clipboard" class="h-7 w-7" />
                </span>
                <p class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Nenhum modelo de anamnese') }}</p>
                <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    {{ __('Crie um modelo com os campos que utiliza na consulta inicial. Depois preencha as respostas deste utente.') }}
                </p>
                <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                    <a
                        href="{{ route('anamnesis-forms.create') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-500"
                    >
                        <x-ui.icon name="plus" class="h-4 w-4" />
                        {{ __('Criar modelo') }}
                    </a>
                    <a
                        href="{{ route('anamnesis-forms.index') }}"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >{{ __('Ver modelos') }}</a>
                </div>
            </div>
        @elseif (!$selectedForm || $selectedForm->questions->isEmpty())
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/60 px-4 py-4 dark:border-amber-900/50 dark:bg-amber-950/30">
                <p class="text-sm font-medium text-amber-900 dark:text-amber-100">{{ __('Este modelo não tem campos configurados.') }}</p>
                <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-200/80">
                    <a href="{{ route('anamnesis-forms.edit', $selectedForm) }}" class="font-semibold underline decoration-amber-600/40 underline-offset-2 hover:decoration-amber-600">{{ __('Editar modelo') }}</a>
                    {{ __('para adicionar perguntas.') }}
                </p>
            </div>
        @else
            <form method="get" action="{{ route('patients.show', $patient) }}" class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-800/40">
                <label class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4" for="anamnesis_form_pick">
                    <span class="shrink-0 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Modelo ativo') }}</span>
                    <select
                        id="anamnesis_form_pick"
                        name="anamnesis_form_id"
                        class="block w-full rounded-xl border-slate-200 bg-white py-2.5 pl-3 pr-10 text-sm shadow-sm transition focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 sm:max-w-md"
                        onchange="this.form.submit()"
                    >
                        @foreach ($anamnesisForms as $f)
                            <option value="{{ $f->id }}" @selected((int) $selectedForm->id === (int) $f->id)>{{ $f->title }}</option>
                        @endforeach
                    </select>
                </label>
            </form>

            <form method="post" action="{{ route('patients.anamnesis.store', $patient) }}" class="mt-6 space-y-5">
                @csrf
                <input type="hidden" name="anamnesis_form_id" value="{{ $selectedForm->id }}" />

                @if ($errors->any())
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-5 rounded-xl border border-slate-100 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
                    @foreach ($selectedForm->questions as $question)
                        <x-anamnesis-field-input
                            :question="$question"
                            name-prefix="answers"
                            :value="$answers[$question->field_key] ?? ''"
                        />
                    @endforeach
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('As respostas são guardadas de forma encriptada na ficha do utente.') }}
                    </p>
                    <x-primary-button type="submit">{{ __('Salvar anamnese') }}</x-primary-button>
                </div>
            </form>
        @endif
    </div>
</div>
