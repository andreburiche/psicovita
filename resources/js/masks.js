import IMask from 'imask';

const maskInstances = new WeakMap();

function destroyMask(el) {
    const existing = maskInstances.get(el);
    if (existing) {
        existing.destroy();
        maskInstances.delete(el);
    }
}

function applyMask(el, type) {
    destroyMask(el);

    const common = { lazy: false };

    switch (type) {
        case 'cpf':
            maskInstances.set(el, IMask(el, { mask: '000.000.000-00', ...common }));
            return;
        case 'cep':
            maskInstances.set(el, IMask(el, { mask: '00000-000', ...common }));
            return;
        case 'phone':
            maskInstances.set(
                el,
                IMask(el, {
                    mask: [
                        { mask: '(00) 0000-0000' },
                        { mask: '(00) 00000-0000' },
                    ],
                    ...common,
                }),
            );
            return;
        case 'date':
            maskInstances.set(el, IMask(el, { mask: '00/00/0000', ...common }));
            return;
        case 'number':
            maskInstances.set(
                el,
                IMask(el, {
                    mask: Number,
                    scale: 2,
                    thousandsSeparator: '',
                    padFractionalZeros: false,
                    normalizeZeros: true,
                    radix: ',',
                    mapToRadix: ['.'],
                }),
            );
            return;
        default:
            return;
    }
}

/**
 * Inicializa IMask em inputs com data-mask (valor = tipo: cpf | phone | cep | date | number).
 */
export function initInputMasks(root = document) {
    root.querySelectorAll('[data-mask]').forEach((el) => {
        if (!(el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement)) {
            return;
        }

        const type = el.dataset.mask;
        if (!type || type === 'email') {
            return;
        }

        applyMask(el, type);
    });
}

export function refreshInputMasks(root = document) {
    root.querySelectorAll('[data-mask]').forEach((el) => {
        destroyMask(el);
    });
    initInputMasks(root);
}
