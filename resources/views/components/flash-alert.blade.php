@props([
    'type' => 'success',
    'message' => null,
    'dismissible' => true,
])

@php
    $message = $message ?? session('status');

    if (is_string($message)) {
        $message = match ($message) {
            'profile-updated' => __('Perfil atualizado com sucesso.'),
            'password-updated' => __('Palavra-passe atualizada com sucesso.'),
            'professional-files-uploaded' => __('Documentos enviados com sucesso.'),
            'professional-file-deleted' => __('Documento removido.'),
            'verification-link-sent' => __('Link de verificação enviado para o seu e-mail.'),
            default => $message,
        };
    }

    $styles = match ($type) {
        'error' => [
            'wrap' => 'border-rose-200/80 bg-gradient-to-r from-rose-50 to-orange-50 text-rose-950 dark:border-rose-800/50 dark:from-rose-950/50 dark:to-orange-950/30 dark:text-rose-100',
            'iconWrap' => 'bg-rose-500/15 text-rose-600 dark:text-rose-400',
            'icon' => 'alert-circle',
        ],
        'warning' => [
            'wrap' => 'border-amber-200/80 bg-gradient-to-r from-amber-50 to-orange-50 text-amber-950 dark:border-amber-800/50 dark:from-amber-950/50 dark:to-orange-950/30 dark:text-amber-100',
            'iconWrap' => 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
            'icon' => 'alert-triangle',
        ],
        'info' => [
            'wrap' => 'border-sky-200/80 bg-gradient-to-r from-sky-50 to-indigo-50 text-sky-950 dark:border-sky-800/50 dark:from-sky-950/50 dark:to-indigo-950/30 dark:text-sky-100',
            'iconWrap' => 'bg-sky-500/15 text-sky-600 dark:text-sky-400',
            'icon' => 'info',
        ],
        default => [
            'wrap' => 'border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-teal-50 text-emerald-950 dark:border-emerald-800/50 dark:from-emerald-950/50 dark:to-teal-950/30 dark:text-emerald-100',
            'iconWrap' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
            'icon' => 'check',
        ],
    };
@endphp

@if (filled($message))
    <div
        {{ $attributes->merge(['class' => 'mb-6']) }}
        @if ($dismissible) x-data="{ show: true }" x-show="show" x-transition.opacity @endif
        role="alert"
    >
        <div class="flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm shadow-sm shadow-slate-900/5 {{ $styles['wrap'] }}">
            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $styles['iconWrap'] }}" aria-hidden="true">
                <x-ui.icon :name="$styles['icon']" class="h-4 w-4" />
            </span>
            <p class="min-w-0 flex-1 pt-0.5 font-medium">{{ $message }}</p>
            @if ($dismissible)
                <button
                    type="button"
                    x-on:click="show = false"
                    class="shrink-0 rounded-lg p-1 text-current/60 transition hover:bg-black/5 hover:text-current dark:hover:bg-white/10"
                    aria-label="{{ __('Fechar') }}"
                >
                    <x-ui.icon name="x" class="h-4 w-4" />
                </button>
            @endif
        </div>
    </div>
@endif
