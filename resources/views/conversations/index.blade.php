@php
    $initials = static function (string $name): string {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(
            mb_substr($parts[0] ?? '?', 0, 1).
            mb_substr($parts[1] ?? '', 0, 1)
        );
    };

    $heroSubtitle = $patientPortal
        ? __('Converse com o seu psicoterapeuta de forma segura. O histórico fica organizado por conversa, com notificações por e-mail.')
        : __('Central de conversas terapêuticas — histórico organizado, privacidade e integração opcional com WhatsApp Business.');
@endphp

@if ($patientPortal)
    <x-patient-layout>
        <x-slot name="header">{{ __('Conversas') }}</x-slot>

        <x-patient-portal-shell>
            <x-patient-portal-breadcrumb :items="[
                ['label' => __('Início'), 'href' => route('patient.home')],
                ['label' => __('Conversas')],
            ]" />

            <x-patient-portal-hero
                :title="__('Conversas')"
                :subtitle="$heroSubtitle"
                icon="chat-bubble-left-right"
            />

            @include('conversations.partials.conversation-tabs')

            @include('conversations.partials.inbox-layout')
        </x-patient-portal-shell>
    </x-patient-layout>
@else
    <x-app-layout>
        <x-slot name="header">{{ __('Conversas') }}</x-slot>
        <div class="mx-auto max-w-7xl space-y-8 px-4 pb-8 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Conversas')"
                :subtitle="$heroSubtitle"
                icon="chat-bubble-left-right"
            />
            @if (Auth::user()?->usesPatientPortalExperience())
                @include('conversations.partials.conversation-tabs')
            @endif
            @include('conversations.partials.inbox-layout')
        </div>
    </x-app-layout>
@endif
