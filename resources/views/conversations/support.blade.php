@php
    $heroSubtitle = __('Canal de apoio da plataforma — cadastro, benefícios, suporte técnico e atendimento humano.');
    $lastMessageId = $messages->last()?->id ?? 0;
@endphp

@if ($patientPortal)
    <x-patient-layout>
        <x-slot name="header">{{ __('Apoio') }}</x-slot>

        <x-patient-portal-shell>
            <x-patient-portal-breadcrumb :items="[
                ['label' => __('Início'), 'href' => route('patient.home')],
                ['label' => __('Conversas'), 'href' => route('conversations.index')],
                ['label' => __('Apoio')],
            ]" />

            <x-patient-portal-hero
                :title="__('Apoio PsiConecta')"
                :subtitle="$heroSubtitle"
                icon="messages"
            />

            @include('conversations.partials.conversation-tabs')
            @include('conversations.partials.support-thread')
        </x-patient-portal-shell>
    </x-patient-layout>
@else
    <x-app-layout>
        <x-slot name="header">{{ __('Apoio') }}</x-slot>
        <div class="mx-auto max-w-3xl space-y-6 px-4 pb-8 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Apoio PsiConecta')"
                :subtitle="$heroSubtitle"
                icon="messages"
            />
            @include('conversations.partials.conversation-tabs')
            @include('conversations.partials.support-thread')
        </div>
    </x-app-layout>
@endif
