@props([
    'patient',
    'portalContext',
])

@php
    $status = $portalContext['status'] ?? 'none';
    $label = $portalContext['label'] ?? '';
    $canInvite = ($portalContext['can_resend'] ?? false) || ($portalContext['can_provision'] ?? false);
    $isFirstInvite = ($portalContext['can_provision'] ?? false) && ! ($portalContext['can_resend'] ?? false);
    $canWhatsApp = ($portalContext['whatsapp_available'] ?? false) && ($portalContext['whatsapp_has_phone'] ?? false);

    $statusTone = match ($status) {
        'active' => [
            'badge' => 'border-emerald-400/60 bg-emerald-600 text-white shadow-emerald-950/30',
            'dot' => 'bg-emerald-200 shadow-[0_0_6px_rgba(167,243,208,0.9)]',
            'icon' => 'check-circle',
            'card' => 'border-emerald-400/25 bg-emerald-950/35',
        ],
        'pending' => [
            'badge' => 'border-amber-400/60 bg-amber-600 text-white shadow-amber-950/30',
            'dot' => 'bg-amber-200 shadow-[0_0_6px_rgba(253,230,138,0.9)]',
            'icon' => 'clock',
            'card' => 'border-amber-400/20 bg-amber-950/25',
        ],
        'inactive' => [
            'badge' => 'border-rose-400/60 bg-rose-600 text-white shadow-rose-950/30',
            'dot' => 'bg-rose-200',
            'icon' => 'exclamation-circle',
            'card' => 'border-rose-400/20 bg-rose-950/25',
        ],
        'no_email' => [
            'badge' => 'border-rose-400/60 bg-rose-600 text-white shadow-rose-950/30',
            'dot' => 'bg-rose-200',
            'icon' => 'mail',
            'card' => 'border-rose-400/20 bg-rose-950/25',
        ],
        default => [
            'badge' => 'border-violet-300/50 bg-violet-700 text-white shadow-violet-950/30',
            'dot' => 'bg-violet-200',
            'icon' => 'user-circle',
            'card' => 'border-white/10 bg-white/[0.06]',
        ],
    };

    $helperText = match ($status) {
        'active' => __('O paciente já pode usar conversas internas, consultas online e documentos.'),
        'pending' => __('Aguarda activação pelo link enviado ao paciente.'),
        'inactive' => __('O convite anterior expirou. Envie um novo convite abaixo.'),
        'no_email' => __('É necessário um e-mail na ficha para criar a conta do portal.'),
        default => __('Crie a conta e envie o link para o paciente definir a palavra-passe.'),
    };
@endphp

<article
    @class([
        'rounded-2xl border p-4 shadow-inner shadow-black/10 backdrop-blur-sm sm:p-5',
        $statusTone['card'],
    ])
    aria-labelledby="portal-patient-heading-{{ $patient->id }}"
>
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 flex-1 text-center sm:text-left">
            <div class="flex flex-col items-center gap-2 sm:flex-row sm:items-center sm:gap-3">
                <h2 id="portal-patient-heading-{{ $patient->id }}" class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/90">
                    {{ __('Portal do paciente') }}
                </h2>
                <span
                    class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-bold uppercase tracking-wide text-white shadow-sm {{ $statusTone['badge'] }}"
                    role="status"
                >
                    <span class="h-2 w-2 shrink-0 rounded-full {{ $statusTone['dot'] }}" aria-hidden="true"></span>
                    <x-ui.icon name="{{ $statusTone['icon'] }}" class="h-4 w-4 shrink-0 text-white" />
                    <span class="text-white">{{ $label }}</span>
                </span>
            </div>
            <p class="mt-2 text-sm leading-relaxed text-white/90">
                {{ $helperText }}
            </p>
            @if ($status === 'pending' && ($portalContext['pending_invitation'] ?? null))
                <p class="mt-1.5 text-xs font-medium text-amber-100/90">
                    {{ __('Expira em :date', ['date' => $portalContext['pending_invitation']->expires_at->format('d/m/Y')]) }}
                </p>
            @endif
        </div>
    </header>

    @if ($status === 'active')
        <div class="mt-4 flex flex-col gap-2 border-t border-white/10 pt-4 sm:flex-row sm:flex-wrap">
            @can('create', \App\Models\ClinicalRecord::class)
                <a
                    href="{{ route('patients.conversation', $patient) }}"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15 sm:flex-none"
                >
                    <x-ui.icon name="chat-bubble-left-right" class="h-4 w-4 shrink-0 text-white" />
                    {{ __('Abrir conversas') }}
                </a>
            @endcan
            @if ($patient->email)
                <span class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-emerald-400/30 bg-emerald-500/15 px-4 py-2.5 text-sm text-emerald-50 sm:flex-none sm:justify-start">
                    <x-ui.icon name="mail" class="h-4 w-4 shrink-0 text-emerald-100" />
                    <span class="truncate">{{ $patient->email }}</span>
                </span>
            @endif
        </div>
    @elseif ($status === 'no_email')
        <div class="mt-4 flex flex-col gap-3 border-t border-white/10 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-center text-sm text-white/85 sm:text-left">
                {{ __('Adicione o e-mail na secção de contacto da ficha.') }}
            </p>
            <a
                href="{{ route('patients.edit', $patient) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 bg-white px-4 py-2.5 text-sm font-semibold text-violet-900 shadow-md transition hover:bg-violet-50"
            >
                <x-ui.icon name="pencil" class="h-4 w-4 shrink-0 text-violet-900" />
                {{ __('Adicionar e-mail') }}
            </a>
        </div>
    @elseif ($canInvite)
        <form
            method="POST"
            action="{{ route('patients.portal-invite.resend', $patient) }}"
            class="mt-4 space-y-4 border-t border-white/10 pt-4"
        >
            @csrf

            @if ($isFirstInvite)
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/20 bg-white/10 p-3.5 transition hover:border-white/30 hover:bg-white/[0.14]">
                    <input
                        type="checkbox"
                        name="portal_lgpd_acknowledged"
                        value="1"
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-white/50 bg-white/20 text-violet-600 focus:ring-2 focus:ring-white/40 focus:ring-offset-0"
                        required
                    />
                    <span class="text-sm leading-snug">
                        <span class="block font-semibold text-white">{{ __('Autorização do paciente') }}</span>
                        <span class="mt-0.5 block text-xs text-white/80">
                            {{ __('Confirmo que o paciente autorizou a criação da conta e o envio do convite.') }}
                        </span>
                    </span>
                </label>
                <x-input-error class="text-xs !text-rose-200" :messages="$errors->get('portal_lgpd_acknowledged')" />
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <fieldset class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                    <legend class="sr-only">{{ __('Canais de envio do convite') }}</legend>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-2 text-sm font-medium text-white transition has-[:checked]:border-violet-200 has-[:checked]:bg-violet-500/40">
                        <input
                            type="checkbox"
                            name="send_portal_invite_email"
                            value="1"
                            class="h-4 w-4 rounded border-white/50 bg-white/20 text-violet-600 focus:ring-white/40"
                            checked
                        />
                        {{ __('E-mail') }}
                    </label>
                    @if ($canWhatsApp)
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-2 text-sm font-medium text-white transition has-[:checked]:border-emerald-200 has-[:checked]:bg-emerald-500/40">
                            <input
                                type="checkbox"
                                name="send_portal_invite_whatsapp"
                                value="1"
                                class="h-4 w-4 rounded border-white/50 bg-white/20 text-emerald-600 focus:ring-white/40"
                                checked
                            />
                            {{ __('WhatsApp') }}
                        </label>
                    @endif
                </fieldset>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-violet-900 shadow-lg shadow-black/20 transition hover:bg-violet-50 sm:w-auto"
                >
                    <x-ui.icon name="paper-airplane" class="h-4 w-4 shrink-0 text-violet-900" />
                    {{ $isFirstInvite ? __('Enviar convite') : __('Reenviar convite') }}
                </button>
            </div>
        </form>
    @endif
</article>
