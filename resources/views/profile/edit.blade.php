@php($patientPortal = auth()->user()->usesPatientPortalExperience())

@if ($patientPortal)
    <x-patient-layout>
        <x-slot name="header">{{ __('A sua conta') }}</x-slot>

        <x-patient-portal-shell>
        <x-patient-portal-breadcrumb :items="[
            ['label' => __('Início'), 'href' => route('patient.home')],
            ['label' => __('Conta')],
        ]" />

        <x-patient-portal-hero
            :title="__('A sua conta')"
            :subtitle="__('Atualize os seus dados, palavra-passe, foto e preferências de segurança.')"
            icon="user"
        />

        <div class="space-y-6">
            @if ($user->isProfessional())
                @include('profile.partials.subscription-section')
                @include('profile.partials.payment-gateway-section')
            @endif
            @include('profile.partials.update-profile-information-form')
            @if ($user->isProfessional() || $user->isAdmin())
                @include('profile.partials.professional-files-form')
            @endif
            @include('profile.partials.update-password-form')
            @include('profile.partials.delete-user-form')
        </div>
        </x-patient-portal-shell>
    </x-patient-layout>
@else
    <x-app-layout>
        <x-slot name="header">{{ __('A sua conta') }}</x-slot>

        <div class="py-8 sm:py-10">
            <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
                <x-page-hero
                    :title="__('A sua conta')"
                    :subtitle="__('Gerencie informações da conta, credenciais e opções de exclusão.')"
                    icon="cog"
                />

                <div class="space-y-6">
                    @if ($user->isProfessional())
                        @unless ($user->isClinicTeamMember())
                            @include('profile.partials.subscription-section')
                            @include('profile.partials.payment-gateway-section')
                        @endunless
                        @include('profile.partials.clinic-team-section')
                    @endif
                    @include('profile.partials.update-profile-information-form')
                    @if ($user->isProfessional() || $user->isAdmin())
                        @include('profile.partials.professional-files-form')
                    @endif
                    @include('profile.partials.update-password-form')
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </x-app-layout>
@endif