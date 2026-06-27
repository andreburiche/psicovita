<x-app-layout>
    <x-slot name="header">{{ __('Solicitação LGPD #:id', ['id' => $dataSubjectRequest->id]) }}</x-slot>

    <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-hero
            :title="__('Solicitação LGPD #:id', ['id' => $dataSubjectRequest->id])"
            :subtitle="$dataSubjectRequest->type->label()"
            icon="shield"
            iconTone="indigo"
        >
            <x-slot name="eyebrow">{{ $dataSubjectRequest->user?->name }}</x-slot>
            <x-slot name="actions">
                <a
                    href="{{ route('admin.lgpd.requests.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    {{ __('Voltar à lista') }}
                </a>
                <span @class(['inline-flex items-center rounded-xl px-3 py-2 text-xs font-semibold', $dataSubjectRequest->status->badgeClass()])>
                    {{ $dataSubjectRequest->status->label() }}
                </span>
            </x-slot>
        </x-page-hero>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Titular') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $dataSubjectRequest->user?->name }}</dd>
                    <dd class="text-sm text-slate-600 dark:text-slate-400">{{ $dataSubjectRequest->user?->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Tipo') }}</dt>
                    <dd class="mt-1 text-slate-900 dark:text-white">{{ $dataSubjectRequest->type->label() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Registrada em') }}</dt>
                    <dd class="mt-1 text-slate-900 dark:text-white">{{ $dataSubjectRequest->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                @if ($dataSubjectRequest->completed_at)
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Concluída em') }}</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $dataSubjectRequest->completed_at->format('d/m/Y H:i') }}</dd>
                    </div>
                @endif
                @if ($dataSubjectRequest->patient)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Ficha vinculada') }}</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">
                            {{ $dataSubjectRequest->patient->name }}
                            @if ($dataSubjectRequest->patient->professional)
                                <span class="text-slate-500">— {{ $dataSubjectRequest->patient->professional->name }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>

            @if ($dataSubjectRequest->details)
                <div class="mt-6 border-t border-slate-100 pt-4 dark:border-slate-700">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Detalhes do titular') }}</p>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300">{{ $dataSubjectRequest->details }}</p>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-4 dark:border-slate-700">
                <a
                    href="{{ route('admin.lgpd.requests.export', $dataSubjectRequest) }}"
                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:border-violet-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                >
                    {{ __('Exportar dados do titular (JSON)') }}
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Atualizar solicitação') }}</h2>

            <form method="POST" action="{{ route('admin.lgpd.requests.update', $dataSubjectRequest) }}" class="mt-6 space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" required class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $dataSubjectRequest->status->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('status')" />
                </div>

                <div>
                    <x-input-label for="response_notes" :value="__('Resposta ao titular (visível no portal)')" />
                    <textarea
                        id="response_notes"
                        name="response_notes"
                        rows="5"
                        maxlength="5000"
                        class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800"
                    >{{ old('response_notes', $dataSubjectRequest->response_notes) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('response_notes')" />
                </div>

                <x-primary-button>{{ __('Salvar e notificar titular') }}</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
