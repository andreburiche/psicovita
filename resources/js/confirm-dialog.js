const VARIANTS = {
    danger: {
        header: 'bg-gradient-to-r from-rose-600 to-orange-600',
        eyebrow: 'text-rose-100',
        subtitle: 'text-rose-50/95',
        iconWrap: 'bg-white/15 ring-white/25',
        confirmBtn: 'bg-rose-600 shadow-rose-600/25 hover:bg-rose-500',
        hint: 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-100',
        hintIcon: 'text-rose-600 dark:text-rose-400',
    },
    warning: {
        header: 'bg-gradient-to-r from-amber-500 to-orange-500',
        eyebrow: 'text-amber-100',
        subtitle: 'text-amber-50/95',
        iconWrap: 'bg-white/15 ring-white/25',
        confirmBtn: 'bg-amber-600 shadow-amber-600/25 hover:bg-amber-500',
        hint: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100',
        hintIcon: 'text-amber-600 dark:text-amber-400',
    },
    primary: {
        header: 'bg-gradient-to-r from-emerald-600 to-sky-600',
        eyebrow: 'text-emerald-100',
        subtitle: 'text-emerald-50/95',
        iconWrap: 'bg-white/15 ring-white/25',
        confirmBtn: 'bg-emerald-600 shadow-emerald-600/25 hover:bg-emerald-500',
        hint: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100',
        hintIcon: 'text-amber-600 dark:text-amber-400',
    },
    benefit: {
        header: 'bg-gradient-to-r from-teal-600 to-emerald-600',
        eyebrow: 'text-teal-100',
        subtitle: 'text-teal-50/95',
        iconWrap: 'bg-white/15 ring-white/25',
        confirmBtn: 'bg-teal-600 shadow-teal-600/25 hover:bg-teal-500',
        hint: 'border-teal-200 bg-teal-50 text-teal-900 dark:border-teal-900/50 dark:bg-teal-950/30 dark:text-teal-100',
        hintIcon: 'text-teal-600 dark:text-teal-400',
    },
};

const DEFAULT_LABELS = {
    confirm: 'Confirmar',
    cancel: 'Cancelar',
};

export function createConfirmDialogData() {
    return {
        isOpen: false,
        title: '',
        message: '',
        hint: '',
        eyebrow: '',
        confirmLabel: DEFAULT_LABELS.confirm,
        cancelLabel: DEFAULT_LABELS.cancel,
        variant: 'danger',
        details: [],
        formId: null,
        lastFocus: null,

        init() {
            window.PsiConectaConfirm = {
                open: (payload) => this.openDialog(payload),
            };

            window.addEventListener('confirm-dialog:open', (event) => {
                this.openDialog(event.detail ?? {});
            });
        },

        focusables() {
            const selector = 'a, button, input:not([type=\'hidden\']), textarea, select, [tabindex]:not([tabindex=\'-1\'])';

            return [...this.$el.querySelectorAll(selector)].filter((el) => ! el.hasAttribute('disabled'));
        },

        openDialog(payload = {}) {
            this.title = payload.title ?? '';
            this.message = payload.message ?? '';
            this.hint = payload.hint ?? '';
            this.eyebrow = payload.eyebrow ?? '';
            this.confirmLabel = payload.confirmLabel ?? DEFAULT_LABELS.confirm;
            this.cancelLabel = payload.cancelLabel ?? DEFAULT_LABELS.cancel;
            this.variant = VARIANTS[payload.variant] ? payload.variant : 'danger';
            this.details = Array.isArray(payload.details) ? payload.details.filter((item) => item?.value) : [];
            this.formId = payload.formId ?? null;
            this.lastFocus = document.activeElement;
            this.isOpen = true;
            document.body.classList.add('overflow-y-hidden');

            this.$nextTick(() => {
                this.focusables()[0]?.focus();
            });
        },

        closeDialog() {
            this.isOpen = false;
            document.body.classList.remove('overflow-y-hidden');
            this.lastFocus?.focus?.();
        },

        submitConfirm() {
            if (this.formId) {
                const form = document.getElementById(this.formId);

                if (form) {
                    form.submit();
                }
            }

            this.closeDialog();
        },

        dialogStyles() {
            return VARIANTS[this.variant] ?? VARIANTS.danger;
        },
    };
}
