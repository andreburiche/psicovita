<x-app-layout>
    <x-slot name="header">{{ __('Novo paciente') }}</x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-patient-breadcrumb :items="[
                ['label' => __('Pacientes'), 'href' => route('patients.index')],
                ['label' => __('Novo paciente')],
            ]" />

            <x-patient-quota-alert :quota="$patientQuota" />

            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50 sm:p-8">
                <div class="mb-6 border-b border-slate-100 pb-6 dark:border-slate-700">
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('Novo paciente') }}</h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Crie a ficha para associar sessões, prontuário, pagamentos e anamnese.') }}</p>
                </div>

                @php
                    $avatarStyle = \App\Support\AvatarStyleOptions::defaults();
                @endphp

                <form
                    method="post"
                    action="{{ route('patients.store') }}"
                    enctype="multipart/form-data"
                    class="space-y-6"
                    x-data="avatarEditor({
                        shape: @js(old('avatar_shape', $avatarStyle['shape'])),
                        ring: @js(old('avatar_ring', $avatarStyle['ring'])),
                        filter: @js(old('avatar_filter', $avatarStyle['filter'])),
                        hasStoredAvatar: false,
                        storedUrl: null,
                    })"
                    @submit="prepareSubmit"
                >
                    @csrf

                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                            <ul class="list-inside list-disc space-y-1">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @include('patients._form', [
                        'patient' => null,
                        'submit' => __('Salvar paciente'),
                        'portalContext' => $portalContext,
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
