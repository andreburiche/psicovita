{{--
  Classes Tailwind das variantes ficam neste Blade (não só no JS),
  para o compilador as incluir no CSS. Evita cabeçalho sem fundo + texto branco.
--}}
<div
    x-data="confirmDialog()"
    x-cloak
    x-show="isOpen"
    x-on:keydown.escape.window="isOpen ? closeDialog() : null"
    class="fixed inset-0 z-[60] overflow-y-auto px-4 py-6 sm:px-0"
    role="dialog"
    aria-modal="true"
    :aria-label="title || @js(__('Confirmar ação'))"
    style="display: none;"
>
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm dark:bg-black/70"
        x-on:click="closeDialog()"
        aria-hidden="true"
    ></div>

    <div class="flex min-h-full items-center justify-center">
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700"
            x-on:click.stop
        >
            <div
                class="px-6 py-5 text-white"
                :class="{
                    'bg-gradient-to-r from-rose-600 to-orange-600': variant === 'danger',
                    'bg-gradient-to-r from-amber-500 to-orange-500': variant === 'warning',
                    'bg-gradient-to-r from-emerald-600 to-sky-600': variant === 'primary',
                    'bg-gradient-to-r from-teal-600 to-emerald-600': variant === 'benefit',
                }"
            >
                <div class="flex items-start gap-4">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25"
                    >
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
                            x-text="eyebrow || @js(__('Confirmação'))"
                        ></p>
                        <h2 class="mt-1 text-lg font-bold leading-snug text-white" x-text="title"></h2>
                        <p
                            class="mt-1 text-sm text-white/90"
                            x-show="message"
                            x-text="message"
                        ></p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 bg-white p-6 dark:bg-slate-900">
                <dl
                    x-show="details.length > 0"
                    class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50/80 p-4 text-sm dark:border-slate-700 dark:bg-slate-800/50"
                >
                    <template x-for="(item, index) in details" :key="index">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="item.label"></dt>
                            <dd class="mt-1 break-words font-medium text-slate-900 dark:text-white" x-text="item.value"></dd>
                        </div>
                    </template>
                </dl>

                <div
                    x-show="hint"
                    class="flex gap-3 rounded-xl border px-4 py-3 text-sm"
                    :class="{
                        'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-100': variant === 'danger',
                        'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100': variant === 'warning' || variant === 'primary',
                        'border-teal-200 bg-teal-50 text-teal-900 dark:border-teal-900/50 dark:bg-teal-950/30 dark:text-teal-100': variant === 'benefit',
                    }"
                >
                    <x-ui.icon
                        name="info"
                        class="mt-0.5 h-5 w-5 shrink-0"
                        x-bind:class="{
                            'text-rose-600 dark:text-rose-400': variant === 'danger',
                            'text-amber-600 dark:text-amber-400': variant === 'warning' || variant === 'primary',
                            'text-teal-600 dark:text-teal-400': variant === 'benefit',
                        }"
                    />
                    <p x-text="hint"></p>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end dark:border-slate-700">
                    <button
                        type="button"
                        x-on:click="closeDialog()"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:focus:ring-offset-slate-900"
                        x-text="cancelLabel"
                    ></button>
                    <button
                        type="button"
                        x-on:click="submitConfirm()"
                        class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-md transition focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                        :class="{
                            'bg-rose-600 shadow-rose-600/25 hover:bg-rose-500 focus:ring-rose-500': variant === 'danger',
                            'bg-amber-600 shadow-amber-600/25 hover:bg-amber-500 focus:ring-amber-500': variant === 'warning',
                            'bg-emerald-600 shadow-emerald-600/25 hover:bg-emerald-500 focus:ring-emerald-500': variant === 'primary',
                            'bg-teal-600 shadow-teal-600/25 hover:bg-teal-500 focus:ring-teal-500': variant === 'benefit',
                        }"
                    >
                        <x-ui.icon name="check" class="h-4 w-4 text-white" />
                        <span x-text="confirmLabel"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
