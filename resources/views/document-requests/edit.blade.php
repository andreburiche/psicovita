<x-app-layout>
    <x-slot name="header">{{ __('Editar solicitação') }}</x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('document-requests.partials.patient-breadcrumb-trail', [
                'patient' => $patient,
                'documentRequest' => $documentRequest,
                'current' => __('Editar'),
            ])

            <x-patient-edit-context :patient="$patient" />

            @if (session('status'))
                <x-ui.success-alert :title="session('status')" />
            @endif

            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50 sm:p-8">
                <div class="mb-6 border-b border-slate-100 pb-6 dark:border-slate-700">
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('Editar solicitação') }}</h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        {{ $documentRequest->institution_name }} · {{ $documentRequest->institution_type->label() }}
                    </p>
                </div>

                <form method="post" action="{{ route('patients.document-requests.update', [$patient, $documentRequest]) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('patch')

                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                            <ul class="list-inside list-disc space-y-1">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @include('document-requests._form', [
                        'documentRequest' => $documentRequest,
                        'submit' => __('Salvar alterações'),
                        'cancelHref' => route('patients.document-requests.show', [$patient, $documentRequest]),
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
