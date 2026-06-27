/**
 * Fallback quando Vite não está ativo: IMask (CDN) + validação leve + ViaCEP.
 * Espelha resources/js/masks.js, field-validation.js e cep-lookup.js.
 */
(function () {
    'use strict';

    var maskInstances = new WeakMap();

    function destroyMask(el) {
        var existing = maskInstances.get(el);
        if (existing) {
            existing.destroy();
            maskInstances.delete(el);
        }
    }

    function applyMask(el, type) {
        if (typeof IMask === 'undefined') {
            return;
        }
        destroyMask(el);
        var common = { lazy: false };
        switch (type) {
            case 'cpf':
                maskInstances.set(el, IMask(el, { mask: '000.000.000-00', lazy: false }));
                return;
            case 'cep':
                maskInstances.set(el, IMask(el, { mask: '00000-000', lazy: false }));
                return;
            case 'phone':
                maskInstances.set(
                    el,
                    IMask(el, {
                        mask: [
                            { mask: '(00) 0000-0000' },
                            { mask: '(00) 00000-0000' },
                        ],
                        lazy: false,
                    }),
                );
                return;
            default:
                return;
        }
    }

    function initInputMasks(root) {
        root = root || document;
        root.querySelectorAll('[data-mask]').forEach(function (el) {
            if (!(el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement)) {
                return;
            }
            var type = el.dataset.mask;
            if (!type || type === 'email') {
                return;
            }
            applyMask(el, type);
        });
    }

    function onlyDigits(value) {
        return String(value ?? '').replace(/\D/g, '');
    }

    function setFieldError(input, message) {
        var wrap = input.closest('[data-field-wrap]');
        var err = wrap && wrap.querySelector('[data-field-error]');
        if (!wrap || !err) {
            return;
        }
        if (message) {
            err.textContent = message;
            err.classList.remove('hidden');
            input.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
            input.setAttribute('aria-invalid', 'true');
        } else {
            err.textContent = '';
            err.classList.add('hidden');
            input.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
            input.removeAttribute('aria-invalid');
        }
    }

    function validCpf(value) {
        var n = onlyDigits(value);
        if (n.length !== 11 || /^(\d)\1{10}$/.test(n)) {
            return false;
        }
        for (var t = 9; t < 11; t++) {
            var d = 0;
            for (var c = 0; c < t; c++) {
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
        var maskType = input.dataset.mask;
        var value = input.value.trim();
        var i18n = window.__PSICONECTA_I18N || {};

        if (input.hasAttribute('required') && value === '') {
            setFieldError(input, i18n.required || 'Campo obrigatório.');
            return;
        }
        if (value === '') {
            setFieldError(input, '');
            return;
        }
        if (maskType === 'cpf') {
            setFieldError(input, validCpf(value) ? '' : i18n.cpf || 'CPF inválido.');
            return;
        }
        if (maskType === 'phone') {
            var pd = onlyDigits(value);
            setFieldError(
                input,
                pd.length >= 10 && pd.length <= 11 ? '' : i18n.phone || 'Telefone inválido.',
            );
            return;
        }
        if (maskType === 'cep') {
            var cd = onlyDigits(value);
            setFieldError(input, cd.length === 8 ? '' : i18n.cep || 'CEP inválido.');
            return;
        }
        setFieldError(input, '');
    }

    function initFieldValidation(root) {
        root = root || document;
        root.querySelectorAll('[data-field-wrap] input, [data-field-wrap] textarea').forEach(function (el) {
            el.addEventListener('blur', function () {
                validateInput(el);
            });
            el.addEventListener('input', function () {
                if (el.getAttribute('aria-invalid') === 'true') {
                    validateInput(el);
                }
            });
        });
    }

    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return (m && m.getAttribute('content')) || '';
    }

    function initCepLookup(root) {
        root = root || document;
        root.querySelectorAll('[data-cep-lookup]').forEach(function (input) {
            if (!(input instanceof HTMLInputElement)) {
                return;
            }
            input.addEventListener(
                'blur',
                function () {
                    var digits = onlyDigits(input.value);
                    if (digits.length !== 8) {
                        return;
                    }
                    var wrap = input.closest('[data-cep-wrap]');
                    var targets = {};
                    try {
                        var raw = wrap && wrap.dataset && wrap.dataset.cepTargets;
                        if (raw) {
                            targets = JSON.parse(raw);
                        }
                    } catch (e) {
                        targets = {};
                    }
                    var url = '/api/cep/' + digits;
                    fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        credentials: 'same-origin',
                    })
                        .then(function (res) {
                            if (!res.ok) {
                                return null;
                            }
                            return res.json();
                        })
                        .then(function (data) {
                            if (!data || data.message) {
                                return;
                            }
                            var map = {
                                street: targets.street,
                                district: targets.district,
                                city: targets.city,
                                state: targets.state,
                            };
                            Object.keys(map).forEach(function (key) {
                                var selector = map[key];
                                if (!selector || typeof selector !== 'string') {
                                    return;
                                }
                                var field = document.querySelector(selector);
                                if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
                                    var val = data[key];
                                    if (typeof val === 'string' && val !== '') {
                                        field.value = val;
                                        field.dispatchEvent(new Event('input', { bubbles: true }));
                                    }
                                }
                            });
                        })
                        .catch(function () {});
                },
                { passive: true },
            );
        });
    }

    function boot() {
        if (typeof IMask === 'undefined') {
            return;
        }
        initInputMasks();
        initFieldValidation();
        initCepLookup();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
