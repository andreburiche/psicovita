@php
    $initials = static function (string $name): string {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(
            mb_substr($parts[0] ?? '?', 0, 1).
            mb_substr($parts[1] ?? '', 0, 1)
        );
    };
    $patientPortal = auth()->user()->usesPatientPortalExperience();
    $patientLayout = $patientPortal;
    $heroSubtitle = $patientPortal
        ? __('As mensagens abaixo são só dentro desta plataforma. O seu profissional não as envia por SMS nem por e-mail automático: deve consultar aqui quando iniciar sessão.')
        : __('Mensagens internas no PsiConecta, mais atalhos para e-mail e WhatsApp (telefone da ficha ou do perfil). O histórico interno fica abaixo. O paciente só vê estas mensagens se entrar em Mensagens na sua conta.');
@endphp

@if ($patientPortal)
    <x-patient-layout>
        <x-slot name="header">{{ __('Mensagens') }}</x-slot>

        <x-patient-portal-shell>
        <x-patient-portal-breadcrumb :items="[
            ['label' => __('Início'), 'href' => route('patient.home')],
            ['label' => __('Mensagens')],
        ]" />

        <x-patient-portal-hero
            :title="__('Mensagens')"
            :subtitle="$heroSubtitle"
            icon="chat-bubble-left-right"
        />

        @include('messages.partials.index-content')
        </x-patient-portal-shell>
    </x-patient-layout>
@else
    <x-app-layout>
        <x-slot name="header">{{ __('Mensagens') }}</x-slot>
        <div class="mx-auto max-w-7xl space-y-8 px-4 pb-8 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Mensagens')"
                :subtitle="$heroSubtitle"
                icon="chat-bubble-left-right"
            />
            @include('messages.partials.index-content')
        </div>
    </x-app-layout>
@endif
