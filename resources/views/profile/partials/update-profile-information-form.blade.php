@php
    $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
    $functionOptions = \App\Enums\UserProfessionalFunction::options();
    $style = $user->resolvedAvatarStyle();
    $uiAccent = old('ui_accent', $user->resolvedUiAccent());
    $accentPresets = \App\Support\UiAccentOptions::presets();
@endphp

<section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
        </span>
        {{ __('Profile Information') }}
    </h3>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
        {{ __("Update your account's profile information and email address.") }}
    </p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form
        method="post"
        action="{{ route('profile.update') }}"
        enctype="multipart/form-data"
        class="mt-4 space-y-6"
        x-data="avatarEditor({
            shape: @js(old('avatar_shape', $style['shape'])),
            ring: @js(old('avatar_ring', $style['ring'])),
            filter: @js(old('avatar_filter', $style['filter'])),
            hasStoredAvatar: @js($user->hasAvatar()),
            storedUrl: @js($user->avatarUrl()),
        })"
        @submit="prepareSubmit"
    >
        @csrf
        @method('patch')

        <input type="file" name="avatar" x-ref="avatarInput" class="sr-only" accept="image/jpeg,image/png,image/webp" />
        <input type="hidden" name="remove_avatar" :value="removeAvatar ? '1' : '0'" />

        <x-avatar-editor
            :display-name="$user->name"
            :initials="$user->avatarInitials()"
            :has-stored-avatar="$user->hasAvatar()"
            :stored-url="$user->avatarUrl()"
            :style="$style"
        />

        <div>
            <x-input-label for="name" :value="__('Name')" class="text-slate-700 dark:text-slate-200" />
            <input
                id="name"
                name="name"
                type="text"
                class="{{ $inputBase }}"
                value="{{ old('name', $user->name) }}"
                required
                autocomplete="name"
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-slate-700 dark:text-slate-200" />
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-violet-400 dark:text-violet-500" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                </span>
                <input
                    id="email"
                    name="email"
                    type="email"
                    class="{{ $inputBase }} pl-10"
                    value="{{ old('email', $user->email) }}"
                    required
                    autocomplete="username"
                    autocapitalize="none"
                    spellcheck="false"
                />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user->isProfessional() || $user->isAdmin())
                <div>
                    <x-input-label for="phone" :value="__('Telefone')" class="text-slate-700 dark:text-slate-200" />
                    <input
                        id="phone"
                        name="phone"
                        type="tel"
                        inputmode="tel"
                        autocomplete="tel"
                        data-mask="phone"
                        class="{{ $inputBase }}"
                        value="{{ old('phone', $user->phone) }}"
                        placeholder="(00) 00000-0000"
                    />
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('Obrigatório para recebimentos via Asaas Connect (mínimo 10 dígitos).') }}
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                </div>
            @endif

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 rounded-xl border border-amber-200/90 bg-amber-50/80 p-3.5 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                    <p>
                        {{ __('Your email address is unverified.') }}
                        <button
                            form="send-verification"
                            type="submit"
                            class="font-semibold text-violet-700 underline decoration-violet-300 underline-offset-2 transition hover:text-violet-900 dark:text-violet-300 dark:hover:text-violet-200"
                        >
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-emerald-700 dark:text-emerald-300">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if ($user->isProfessional() || $user->isAdmin())
            <div>
                <x-input-label for="professional_function" :value="__('Função')" class="text-slate-700 dark:text-slate-200" />
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-violet-400 dark:text-violet-500" aria-hidden="true">
                        <x-ui.icon name="briefcase" class="h-4 w-4" />
                    </span>
                    <select
                        id="professional_function"
                        name="professional_function"
                        class="{{ $inputBase }} pl-10"
                        required
                    >
                    <option value="" disabled @selected(! old('professional_function', $user->professional_function?->value))>{{ __('Selecione a sua função') }}</option>
                    @foreach ($functionOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('professional_function', $user->professional_function?->value) === $value)>{{ $label }}</option>
                    @endforeach
                    </select>
                </div>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Como você atua profissionalmente (ex.: psicólogo, psicoterapeuta).') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('professional_function')" />
            </div>

            <div>
                <x-input-label for="crp_number" :value="__('Número do CRP')" class="text-slate-700 dark:text-slate-200" />
                <input
                    id="crp_number"
                    name="crp_number"
                    type="text"
                    class="{{ $inputBase }}"
                    value="{{ old('crp_number', $user->crp_number) }}"
                    autocomplete="off"
                    placeholder="00/000000"
                />
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Usado na geração de ofícios de solicitação de documentos.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('crp_number')" />
            </div>

            @if ($user->isClinicOwner())
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-violet-50/50 via-white to-indigo-50/30 dark:border-slate-700 dark:from-violet-950/20 dark:via-slate-900/80 dark:to-indigo-950/20">
                    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700">
                        <h4 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300" aria-hidden="true">
                                <x-ui.icon name="document-text" class="h-3.5 w-3.5" />
                            </span>
                            {{ __('Logo da instituição') }}
                        </h4>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Aparece no cabeçalho de atestados, declarações e receitas (PDF e pré-visualização).') }}</p>
                    </div>
                    <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-start">
                        <div class="flex h-24 w-full max-w-[12rem] items-center justify-center rounded-xl border border-dashed border-slate-200 bg-white p-3 dark:border-slate-600 dark:bg-slate-900/60">
                            @if ($user->hasInstitutionLogo())
                                <img src="{{ $user->institutionLogoUrl() }}" alt="{{ __('Logo da instituição') }}" class="max-h-full max-w-full object-contain" />
                            @else
                                <span class="text-center text-xs text-slate-400">{{ __('Nenhuma logo enviada') }}</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1 space-y-3">
                            <div>
                                <x-input-label for="institution_logo" :value="__('Enviar logo da instituição')" class="text-slate-700 dark:text-slate-200" />
                                <input
                                    id="institution_logo"
                                    name="institution_logo"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                    class="mt-1.5 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-violet-700 hover:file:bg-violet-100 dark:text-slate-300 dark:file:bg-violet-950 dark:file:text-violet-300"
                                    x-on:change="
                                        const file = $event.target.files?.[0];
                                        if (!file) return;
                                        if (file.size > 8 * 1024 * 1024) {
                                            $event.target.value = '';
                                            window.alert(@js(__('A logo da instituição deve ter no máximo 8 MB. Isto é diferente da foto de perfil (acima).')));
                                        }
                                    "
                                />
                                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Campo separado da foto de perfil. PNG, JPG, WebP ou SVG — até 8 MB. Usado só nos PDFs clínicos.') }}</p>
                                <x-input-error class="mt-2" :messages="$errors->get('institution_logo')" />
                            </div>
                            @if ($user->hasInstitutionLogo())
                                <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                    <input type="checkbox" name="remove_institution_logo" value="1" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500" />
                                    {{ __('Remover logo atual') }}
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif ($user->isClinicTeamMember() && $user->clinicOwner?->hasInstitutionLogo())
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-400">
                    {{ __('A logo nos documentos clínicos é definida pelo titular da clínica (:name).', ['name' => $user->clinicOwner->name]) }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-slate-50/80 via-white to-violet-50/30 dark:border-slate-700 dark:from-slate-900/60 dark:via-slate-900/80 dark:to-violet-950/20">
                <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700">
                    <h4 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300" aria-hidden="true">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </span>
                        {{ __('Descrição profissional') }}
                    </h4>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Apresentação breve: formação, abordagem, áreas de atuação e experiência.') }}</p>
                </div>
                <div class="p-4">
                    <textarea
                        id="professional_bio"
                        name="professional_bio"
                        rows="6"
                        class="{{ $inputBase }} resize-y min-h-[8rem]"
                        placeholder="{{ __('Ex.: Psicóloga clínica, abordagem TCC, atendimento adulto e adolescente…') }}"
                    >{{ old('professional_bio', $user->professional_bio) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('professional_bio')" />
                </div>
            </div>
        @endif

        <div
            class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60"
            x-data="{ accent: @js($uiAccent) }"
        >
            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-4 py-3 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900/90">
                <h4 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300" aria-hidden="true">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.742-3.468A8.967 8.967 0 0112 21a8.967 8.967 0 01-6.268-2.346M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    {{ __('Aparência da aplicação') }}
                </h4>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Escolha a cor de fundo da interface. A alteração é aplicada imediatamente e guardada na sua conta.') }}</p>
            </div>
            <div class="p-4">
                <fieldset>
                    <legend class="sr-only">{{ __('Cor de fundo') }}</legend>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ($accentPresets as $key => $preset)
                            <label
                                class="group flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm transition hover:border-violet-300/70 hover:shadow-md has-[:checked]:border-violet-500 has-[:checked]:ring-2 has-[:checked]:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-900/60 dark:hover:border-violet-500/50 dark:has-[:checked]:border-violet-500"
                                @click="accent = @js($key); window.psiApplyAccent && window.psiApplyAccent(@js($key))"
                            >
                                <input
                                    type="radio"
                                    name="ui_accent"
                                    value="{{ $key }}"
                                    x-model="accent"
                                    class="sr-only"
                                    @checked($uiAccent === $key)
                                />
                                <span
                                    class="h-10 w-full rounded-lg shadow-inner ring-1 ring-black/5 dark:ring-white/10"
                                    style="background: linear-gradient(to bottom right, {{ $preset['light']['from'] }}, {{ $preset['light']['via'] }}, {{ $preset['light']['to'] }});"
                                    aria-hidden="true"
                                ></span>
                                <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                    <span class="h-2.5 w-2.5 rounded-full ring-1 ring-black/10" style="background-color: {{ $preset['swatch'] }}" aria-hidden="true"></span>
                                    {{ __($preset['label']) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <x-input-error class="mt-2" :messages="$errors->get('ui_accent')" />
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-end dark:border-slate-800">
            <x-primary-button class="justify-center sm:justify-start">{{ __('Save') }}</x-primary-button>
        </div>
    </form>
</section>
