@props([
    'portalContext',
])

@php
    $canProvision = $portalContext['can_provision'] ?? false;
    $status = $portalContext['status'] ?? 'none';
    $label = $portalContext['label'] ?? '';
    $checkboxCard = 'flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200/90 bg-white p-4 text-sm text-slate-700 shadow-sm transition hover:border-violet-300/70 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50/50 dark:border-slate-600 dark:bg-slate-900/60 dark:text-slate-200 dark:has-[:checked]:border-violet-500 dark:has-[:checked]:bg-violet-950/30';
@endphp

<section
    class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60"
    x-data="{ createPortal: @js((bool) old('create_portal_access', false)) }"
>
    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300" aria-hidden="true">
            <x-ui.icon name="chat-bubble-left-right" class="h-4 w-4" />
        </span>
        {{ __('Portal do paciente') }}
    </h3>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
        {{ __('Crie acesso para conversas internas, consultas online e documentos no portal.') }}
    </p>

    @unless ($canProvision)
        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-800/50 dark:text-slate-200">
            <p class="font-semibold">{{ $label }}</p>
            @if ($status === 'no_email')
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Adicione o e-mail na secção de contacto para convidar o paciente.') }}</p>
            @elseif ($status === 'pending')
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('O paciente deve activar o acesso pelo link enviado por e-mail ou WhatsApp. Use «Reenviar convite» na ficha do paciente.') }}</p>
            @elseif ($status === 'inactive')
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('O convite expirou. Use «Reenviar convite» na ficha do paciente.') }}</p>
            @elseif ($status === 'none')
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Marque as opções abaixo e use «Enviar convite» na ficha do paciente, ou active «Criar acesso ao portal» ao guardar esta edição.') }}</p>
            @elseif ($status === 'active')
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Conversas internas e portal já estão disponíveis para este paciente.') }}</p>
            @endif
        </div>
    @else
        <div class="mt-4 space-y-3">
            <label class="{{ $checkboxCard }}">
                <input
                    type="checkbox"
                    name="create_portal_access"
                    value="1"
                    class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                    x-model="createPortal"
                    @checked(old('create_portal_access'))
                />
                <span>
                    <span class="block font-semibold text-slate-900 dark:text-white">{{ __('Criar acesso ao portal') }}</span>
                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Requer e-mail do paciente. Será criada uma conta para conversas e área do paciente.') }}</span>
                </span>
            </label>

            <div x-show="createPortal" x-cloak class="space-y-3">
                <label class="{{ $checkboxCard }}">
                    <input
                        type="checkbox"
                        name="send_portal_invite_email"
                        value="1"
                        class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                        :disabled="!createPortal"
                        @checked(old('send_portal_invite_email', true))
                    />
                    <span>
                        <span class="block font-semibold text-slate-900 dark:text-white">{{ __('Enviar convite por e-mail') }}</span>
                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Link seguro para o paciente definir a palavra-passe.') }}</span>
                    </span>
                </label>

                @if (($portalContext['whatsapp_available'] ?? false) && ($portalContext['whatsapp_has_phone'] ?? false))
                    <label class="{{ $checkboxCard }}">
                        <input
                            type="checkbox"
                            name="send_portal_invite_whatsapp"
                            value="1"
                            class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                            :disabled="!createPortal"
                            @checked(old('send_portal_invite_whatsapp', true))
                        />
                        <span>
                            <span class="block font-semibold text-slate-900 dark:text-white">{{ __('Enviar convite por WhatsApp') }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Mensagem com o mesmo link de activação (sem senha em texto).') }}</span>
                        </span>
                    </label>
                @elseif (($portalContext['whatsapp_available'] ?? false) && ! ($portalContext['whatsapp_has_phone'] ?? false))
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Para enviar por WhatsApp, adicione o telefone do paciente.') }}</p>
                @endif

                <label class="{{ $checkboxCard }}">
                    <input
                        type="checkbox"
                        name="portal_lgpd_acknowledged"
                        value="1"
                        class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                        :disabled="!createPortal"
                        @checked(old('portal_lgpd_acknowledged'))
                    />
                    <span>
                        <span class="block font-semibold text-slate-900 dark:text-white">{{ __('Autorização do paciente') }}</span>
                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Confirmo que o paciente autorizou a criação da conta e o envio do convite por e-mail e/ou WhatsApp.') }}</span>
                    </span>
                </label>
            </div>
        </div>

        <x-input-error class="mt-3" :messages="$errors->get('portal_lgpd_acknowledged')" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    @endunless
</section>
