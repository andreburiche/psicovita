<div class="flex items-start gap-4">
    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25">
        <template x-if="variant === 'primary'">
            <x-ui.icon name="check-badge" class="h-6 w-6 text-white" />
        </template>
        <template x-if="variant === 'benefit'">
            <x-ui.icon name="sparkles" class="h-6 w-6 text-white" />
        </template>
        <template x-if="variant !== 'primary' && variant !== 'benefit'">
            <x-ui.icon name="alert-triangle" class="h-6 w-6 text-white" />
        </template>
    </span>
    <div class="min-w-0 pt-0.5">
        <p
            class="text-xs font-bold uppercase tracking-wider text-white/85"
            style="color: rgba(255, 255, 255, 0.9);"
            x-text="eyebrow || @js(__('Confirmação'))"
        ></p>
        <h2 class="mt-1 text-lg font-bold leading-snug text-white" style="color: #ffffff;" x-text="title"></h2>
        <p
            class="mt-1 text-sm text-white/90"
            style="color: rgba(255, 255, 255, 0.92);"
            x-show="message"
            x-text="message"
        ></p>
    </div>
</div>
