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
            <div class="px-6 py-5 text-white" :class="dialogStyles().header">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-1" :class="dialogStyles().iconWrap">
                        <template x-if="variant === 'primary'">
                            <x-ui.icon name="check-badge" class="h-6 w-6" />
                        </template>
                        <template x-if="variant !== 'primary'">
                            <x-ui.icon name="alert-triangle" class="h-6 w-6" />
                        </template>
                    </span>
                    <div class="min-w-0 pt-0.5">
                        <p class="text-xs font-bold uppercase tracking-wider" :class="dialogStyles().eyebrow" x-text="eyebrow || @js(__('Confirmação'))"></p>
                        <h2 class="mt-1 text-lg font-bold leading-snug" x-text="title"></h2>
                        <p class="mt-1 text-sm" :class="dialogStyles().subtitle" x-show="message" x-text="message"></p>
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
                    :class="dialogStyles().hint"
                >
                    <x-ui.icon name="info" class="mt-0.5 h-5 w-5 shrink-0" x-bind:class="dialogStyles().hintIcon" />
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
                        :class="dialogStyles().confirmBtn"
                    >
                        <x-ui.icon name="check" class="h-4 w-4" />
                        <span x-text="confirmLabel"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
