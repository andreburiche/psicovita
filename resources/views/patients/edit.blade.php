<x-app-layout>
    <x-slot name="header">{{ __('Editar paciente') }}</x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-patient-breadcrumb :items="[
                ['label' => __('Pacientes'), 'href' => route('patients.index')],
                ['label' => $patient->name, 'href' => route('patients.show', $patient)],
                ['label' => __('Editar')],
            ]" />

            <x-patient-edit-context :patient="$patient" />

            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50 sm:p-8">
                <div class="mb-6 border-b border-slate-100 pb-6 dark:border-slate-700">
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('Editar paciente') }}</h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Atualize dados da ficha. Observações são encriptadas.') }}</p>
                </div>

            @php
                $avatarStyle = $patient->resolvedAvatarStyle();
                $avatarSyncNote = $patient->portalUser()
                    ? __('Sincronizada com a conta do paciente no portal — alterações aqui refletem-se no perfil e vice-versa.')
                    : ($patient->email
                        ? __('Será sincronizada automaticamente quando existir conta ativa com o mesmo e-mail.')
                        : __('Adicione o e-mail do paciente para sincronizar a foto com a conta no portal.'));
            @endphp

            <form
                method="post"
                action="{{ route('patients.update', $patient) }}"
                enctype="multipart/form-data"
                class="space-y-6"
                x-data="avatarEditor({
                    shape: @js(old('avatar_shape', $avatarStyle['shape'])),
                    ring: @js(old('avatar_ring', $avatarStyle['ring'])),
                    filter: @js(old('avatar_filter', $avatarStyle['filter'])),
                    hasStoredAvatar: @js($patient->hasAvatar()),
                    storedUrl: @js($patient->avatarUrl()),
                })"
                @submit="prepareSubmit"
            >
                @csrf
                @method('put')

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
                    'patient' => $patient,
                    'submit' => __('Salvar alterações'),
                    'avatarSyncNote' => $avatarSyncNote,
                    'portalContext' => $portalContext,
                ])
            </form>
            </div>
        </div>
    </div>
</x-app-layout>
