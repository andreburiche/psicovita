@props([
    'subscription',
    'manualActivationEnabled' => true,
])

@php
    $isComplimentary = $subscription->hasComplimentaryAccess();
    $needsValidation = $subscription->isAwaitingAdminValidation()
        || in_array($subscription->status->value, ['past_due', 'expired'], true);
    $validateLabel = $needsValidation ? __('Validar') : __('Ajustar');
    $validateTitle = $needsValidation
        ? __('Validar pagamento / activar plano')
        : __('Renovar ou ajustar plano');
    $professionalName = $subscription->user?->name ?? __('profissional');
    $planName = $subscription->plan?->name ?? __('Clínica');
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-end']) }}>
    <div
        class="inline-flex max-w-full items-stretch overflow-hidden rounded-lg border border-slate-200 bg-slate-50/80 shadow-sm dark:border-slate-600 dark:bg-slate-800/80"
        role="group"
        aria-label="{{ __('Ações da assinatura') }}"
    >
        @if ($manualActivationEnabled)
            <a
                href="{{ route('admin.subscriptions.validate', $subscription) }}"
                title="{{ $validateTitle }}"
                @class([
                    'inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[11px] font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px]',
                    'bg-emerald-600 text-white hover:bg-emerald-500 focus-visible:outline-emerald-500' => $needsValidation,
                    'text-slate-700 hover:bg-white focus-visible:outline-violet-500 dark:text-slate-200 dark:hover:bg-slate-700' => ! $needsValidation,
                ])
            >
                <x-ui.icon :name="$needsValidation ? 'banknote' : 'pencil'" class="h-3.5 w-3.5 shrink-0 opacity-90" />
                <span class="whitespace-nowrap">{{ $validateLabel }}</span>
            </a>

            <span class="w-px self-stretch bg-slate-200 dark:bg-slate-600" aria-hidden="true"></span>
        @endif

        @if ($isComplimentary)
            <x-confirm-form
                method="POST"
                action="{{ route('admin.subscriptions.complimentary.revoke', $subscription) }}"
                class="contents"
                :eyebrow="__('Benefício')"
                :title="__('Desactivar acesso por cortesia?')"
                :message="__('O profissional perderá o acesso gratuito e voltará a depender de uma assinatura válida.')"
                :hint="__('Pode voltar a activar a cortesia a qualquer momento nesta lista.')"
                :confirm-label="__('Sim, desactivar')"
                :cancel-label="__('Manter cortesia')"
                variant="danger"
                :validate="false"
                :details="[
                    ['label' => __('Profissional'), 'value' => $professionalName],
                    ['label' => __('Plano actual'), 'value' => $planName],
                ]"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    title="{{ __('Desactivar acesso por cortesia') }}"
                    class="inline-flex h-full w-full items-center gap-1.5 px-2.5 py-1.5 text-[11px] font-semibold text-rose-700 transition hover:bg-rose-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-rose-500 dark:text-rose-300 dark:hover:bg-rose-950/50"
                >
                    <x-ui.icon name="ban" class="h-3.5 w-3.5 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('Revogar') }}</span>
                </button>
            </x-confirm-form>
        @else
            <x-confirm-form
                method="POST"
                action="{{ route('admin.subscriptions.complimentary.grant', $subscription) }}"
                class="contents"
                :eyebrow="__('Benefício')"
                :title="__('Activar acesso por cortesia?')"
                :message="__('O profissional terá acesso completo à aplicação sem necessidade de pagamento.')"
                :hint="__('Ideal para parceiros, demos ou contas convidadas. Pode revogar quando quiser.')"
                :confirm-label="__('Activar cortesia')"
                :cancel-label="__('Cancelar')"
                variant="benefit"
                :validate="false"
                :details="[
                    ['label' => __('Profissional'), 'value' => $professionalName],
                    ['label' => __('Inclui'), 'value' => __('Todas as funcionalidades · sem limite de pacientes')],
                ]"
            >
                @csrf
                <button
                    type="submit"
                    title="{{ __('Activar acesso completo sem pagamento') }}"
                    class="inline-flex h-full w-full items-center gap-1.5 px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 transition hover:bg-white hover:text-teal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-teal-500 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-teal-300"
                >
                    <x-ui.icon name="sparkles" class="h-3.5 w-3.5 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('Cortesia') }}</span>
                </button>
            </x-confirm-form>
        @endif
    </div>
</div>
