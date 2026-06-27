/**
 * Validação em tempo real (UX). O Laravel continua sendo a fonte da verdade.
 */

function onlyDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function setFieldError(input, message) {
    const wrap = input.closest('[data-field-wrap]');
    const err = wrap?.querySelector('[data-field-error]');
    if (!wrap || !err) {
        return;
    }
    if (message) {
        if (! err.id) {
            err.id = `field-error-${input.id || input.name || 'input'}`;
        }
        err.textContent = message;
        err.classList.remove('hidden');
        input.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
        input.setAttribute('aria-invalid', 'true');
        input.setAttribute('aria-describedby', err.id);
    } else {
        err.textContent = '';
        err.classList.add('hidden');
        input.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
        input.removeAttribute('aria-invalid');
        if (input.getAttribute('aria-describedby') === err.id) {
            input.removeAttribute('aria-describedby');
        }
    }
}

function validCpf(value) {
    const n = onlyDigits(value);
    if (n.length !== 11 || /^(\d)\1{10}$/.test(n)) {
        return false;
    }
    for (let t = 9; t < 11; t++) {
        let d = 0;
        for (let c = 0; c < t; c++) {
            d += parseInt(n[c], 10) * (t + 1 - c);
        }
        d = ((10 * d) % 11) % 10;
        if (d !== parseInt(n[t], 10)) {
            return false;
        }
    }
    return true;
}

function validateInput(input) {
    const maskType = input.dataset.mask;
    const value = input.value.trim();

    if (input.hasAttribute('required') && value === '') {
        setFieldError(input, window.__PSICONECTA_I18N?.required ?? 'Campo obrigatório.');
        return;
    }

    if (value === '') {
        setFieldError(input, '');
        return;
    }

    if (maskType === 'cpf') {
        setFieldError(input, validCpf(value) ? '' : (window.__PSICONECTA_I18N?.cpf ?? 'CPF inválido.'));
        return;
    }

    if (maskType === 'phone') {
        const d = onlyDigits(value);
        setFieldError(
            input,
            d.length >= 10 && d.length <= 11 ? '' : (window.__PSICONECTA_I18N?.phone ?? 'Telefone inválido.'),
        );
        return;
    }

    if (maskType === 'cep') {
        const d = onlyDigits(value);
        setFieldError(input, d.length === 8 ? '' : (window.__PSICONECTA_I18N?.cep ?? 'CEP inválido.'));
        return;
    }

    if (maskType === 'date') {
        const ok = /^\d{2}\/\d{2}\/\d{4}$/.test(value);
        setFieldError(input, ok ? '' : (window.__PSICONECTA_I18N?.date ?? 'Data inválida (use dd/mm/aaaa).'));
        return;
    }

    if (input.type === 'email' || input.dataset.fieldType === 'email') {
        const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        setFieldError(input, ok ? '' : (window.__PSICONECTA_I18N?.email ?? 'E-mail inválido.'));
        return;
    }

    setFieldError(input, '');
}

export function initFieldValidation(root = document) {
    root.querySelectorAll('[data-field-wrap] input, [data-field-wrap] textarea').forEach((el) => {
        el.addEventListener('blur', () => validateInput(el));
        el.addEventListener('input', () => {
            if (el.getAttribute('aria-invalid') === 'true') {
                validateInput(el);
            }
        });
    });
}
