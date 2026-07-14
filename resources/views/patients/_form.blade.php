@php
    $p = $patient;
    $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
    $cepTargets = [
        'street' => '#address_street',
        'district' => '#address_district',
        'city' => '#address_city',
        'state' => '#address_state',
    ];
    $avatarStyle = $p ? $p->resolvedAvatarStyle() : \App\Support\AvatarStyleOptions::defaults();
    $avatarDisplayName = old('name', $p?->name ?? '');
    $avatarInitials = $p
        ? $p->avatarInitials()
        : (mb_strlen(trim($avatarDisplayName)) > 0 ? mb_strtoupper(mb_substr(trim($avatarDisplayName), 0, 1)) : '?');
    $avatarSyncNote = $avatarSyncNote ?? null;
@endphp

<input type="file" name="avatar" x-ref="avatarInput" class="sr-only" accept="image/jpeg,image/png,image/webp" />
<input type="hidden" name="remove_avatar" :value="removeAvatar ? '1' : '0'" />

<x-avatar-editor
    :display-name="$avatarDisplayName"
    :initials="$avatarInitials"
    :has-stored-avatar="$p?->hasAvatar() ?? false"
    :stored-url="$p?->avatarUrl()"
    :style="$avatarStyle"
    :sync-note="$avatarSyncNote"
/>

<section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400" aria-hidden="true">
            <x-ui.icon name="users" class="h-4 w-4" />
        </span>
        {{ __('Identificação') }}
    </h3>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Dados básicos do paciente para localizar e abrir a ficha.') }}</p>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="name" :value="__('Nome')" class="text-slate-700 dark:text-slate-200" />
            <input id="name" name="name" type="text" class="{{ $inputBase }}" value="{{ old('name', $p?->name) }}" required autofocus />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>
        <div>
            <x-input-label for="birth_date" :value="__('Data de nascimento')" class="text-slate-700 dark:text-slate-200" />
            <input id="birth_date" name="birth_date" type="date" class="{{ $inputBase }}" value="{{ old('birth_date', $p?->birth_date?->format('Y-m-d')) }}" />
            <x-input-error class="mt-2" :messages="$errors->get('birth_date')" />
        </div>
        <div data-field-wrap>
            <x-input-label for="cpf" :value="__('CPF')" class="text-slate-700 dark:text-slate-200" />
            <input
                id="cpf"
                name="cpf"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                data-mask="cpf"
                data-field-type="cpf"
                class="{{ $inputBase }}"
                value="{{ old('cpf', $p?->cpf ? format_cpf_human($p->cpf) : '') }}"
                placeholder="000.000.000-00"
            />
            <p class="mt-1 hidden text-sm text-rose-600 dark:text-rose-400" data-field-error role="alert"></p>
            <x-input-error class="mt-2" :messages="$errors->get('cpf')" />
        </div>
    </div>
</section>

<section
    class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60"
    data-cep-wrap
    data-cep-targets='@json($cepTargets)'
>
    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300" aria-hidden="true">
            <x-ui.icon name="map-pin" class="h-4 w-4" />
        </span>
        {{ __('Endereço') }}
    </h3>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Opcional. Ao sair do CEP válido, tentamos preencher rua, bairro, cidade e UF (ViaCEP).') }}</p>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div data-field-wrap>
            <x-input-label for="address_postal_code" :value="__('CEP')" class="text-slate-700 dark:text-slate-200" />
            <input
                id="address_postal_code"
                name="address_postal_code"
                type="text"
                inputmode="numeric"
                autocomplete="postal-code"
                data-mask="cep"
                data-field-type="cep"
                data-cep-lookup
                class="{{ $inputBase }}"
                value="{{ old('address_postal_code', $p?->address_postal_code ? format_cep_human($p->address_postal_code) : '') }}"
                placeholder="00000-000"
            />
            <p class="mt-1 hidden text-sm text-rose-600 dark:text-rose-400" data-field-error role="alert"></p>
            <x-input-error class="mt-2" :messages="$errors->get('address_postal_code')" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="address_street" :value="__('Logradouro')" class="text-slate-700 dark:text-slate-200" />
            <input id="address_street" name="address_street" type="text" class="{{ $inputBase }}" value="{{ old('address_street', $p?->address_street) }}" autocomplete="street-address" />
            <x-input-error class="mt-2" :messages="$errors->get('address_street')" />
        </div>
        <div>
            <x-input-label for="address_number" :value="__('Número')" class="text-slate-700 dark:text-slate-200" />
            <input id="address_number" name="address_number" type="text" class="{{ $inputBase }}" value="{{ old('address_number', $p?->address_number) }}" />
            <x-input-error class="mt-2" :messages="$errors->get('address_number')" />
        </div>
        <div>
            <x-input-label for="address_complement" :value="__('Complemento')" class="text-slate-700 dark:text-slate-200" />
            <input id="address_complement" name="address_complement" type="text" class="{{ $inputBase }}" value="{{ old('address_complement', $p?->address_complement) }}" />
            <x-input-error class="mt-2" :messages="$errors->get('address_complement')" />
        </div>
        <div>
            <x-input-label for="address_district" :value="__('Bairro')" class="text-slate-700 dark:text-slate-200" />
            <input id="address_district" name="address_district" type="text" class="{{ $inputBase }}" value="{{ old('address_district', $p?->address_district) }}" />
            <x-input-error class="mt-2" :messages="$errors->get('address_district')" />
        </div>
        <div>
            <x-input-label for="address_city" :value="__('Cidade')" class="text-slate-700 dark:text-slate-200" />
            <input id="address_city" name="address_city" type="text" class="{{ $inputBase }}" value="{{ old('address_city', $p?->address_city) }}" />
            <x-input-error class="mt-2" :messages="$errors->get('address_city')" />
        </div>
        <div>
            <x-input-label for="address_state" :value="__('UF')" class="text-slate-700 dark:text-slate-200" />
            <input
                id="address_state"
                name="address_state"
                type="text"
                maxlength="2"
                class="{{ $inputBase }} uppercase"
                value="{{ old('address_state', $p?->address_state) }}"
                placeholder="SP"
                autocomplete="address-level1"
            />
            <x-input-error class="mt-2" :messages="$errors->get('address_state')" />
        </div>
    </div>
</section>

<section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300" aria-hidden="true">
            <x-ui.icon name="mail" class="h-4 w-4" />
        </span>
        {{ __('Contato') }}
    </h3>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Opcional, mas ajuda para comunicação e acesso do paciente à plataforma.') }}</p>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="email" :value="__('E-mail')" class="text-slate-700 dark:text-slate-200" />
            <input id="email" name="email" type="email" class="{{ $inputBase }}" value="{{ old('email', $p?->email) }}" autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div data-field-wrap>
            <x-input-label for="phone" :value="__('Telefone')" class="text-slate-700 dark:text-slate-200" />
            <input
                id="phone"
                name="phone"
                type="text"
                data-mask="phone"
                data-field-type="phone"
                class="{{ $inputBase }}"
                value="{{ format_phone_br_human(old('phone', $p?->phone)) }}"
                placeholder="(00) 00000-0000"
                autocomplete="tel"
            />
            <p class="mt-1 hidden text-sm text-rose-600 dark:text-rose-400" data-field-error role="alert"></p>
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>
    </div>
</section>

@php
    $resolvedPortalContext = $portalContext ?? app(\App\Services\PatientPortalProvisioningService::class)->statusContext($p ?? new \App\Models\Patient());
    $showPortalAccessForm = $p === null || ($resolvedPortalContext['can_provision'] ?? false);
@endphp

@if ($showPortalAccessForm)
    @include('patients.partials.portal-access-form', ['portalContext' => $resolvedPortalContext])
@elseif ($p !== null)
    <div class="rounded-2xl border border-slate-200/90 bg-slate-50 px-5 py-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-300">
        <p class="font-semibold text-slate-800 dark:text-slate-100">{{ __('Portal do paciente') }}: {{ $resolvedPortalContext['label'] ?? '' }}</p>
        <p class="mt-1 text-xs">{{ __('Para reenviar convite ou ver o estado, abra a ficha do paciente (não é necessário alterar nada aqui).') }}</p>
    </div>
@endif

<section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
    <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-4 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900/90">
        <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300" aria-hidden="true">
                <x-ui.icon name="document-text" class="h-4 w-4" />
            </span>
            {{ __('Observações') }}
        </h3>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Conteúdo interno. Guardado de forma encriptada.') }}</p>
    </div>
    <div class="p-5">
        <textarea id="notes" name="notes" rows="6" class="{{ $inputBase }}" placeholder="{{ __('Observações clínicas, contexto, preferências do paciente…') }}">{{ old('notes', $p?->notes) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
    </div>
</section>

<div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
    <a href="{{ $p ? route('patients.show', $p) : route('patients.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">{{ __('Cancelar') }}</a>
    <x-primary-button class="justify-center sm:justify-start">{{ $submit }}</x-primary-button>
</div>
