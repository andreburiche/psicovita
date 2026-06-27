function onlyDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/**
 * ViaCEP preenchimento opcional via data-cep-targets JSON no wrapper:
 * { "street": "#id-rua", "district": "#id-bairro", "city": "#id-cidade", "state": "#id-estado" }
 */
export function initCepLookup(root = document) {
    root.querySelectorAll('[data-cep-lookup]').forEach((input) => {
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        input.addEventListener(
            'blur',
            async () => {
                const digits = onlyDigits(input.value);
                if (digits.length !== 8) {
                    return;
                }

                const wrap = input.closest('[data-cep-wrap]');
                let targets = {};
                try {
                    const raw = wrap?.dataset?.cepTargets;
                    if (raw) {
                        targets = JSON.parse(raw);
                    }
                } catch {
                    targets = {};
                }

                try {
                    const url = `/api/cep/${digits}`;
                    const res = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        credentials: 'same-origin',
                    });

                    if (!res.ok) {
                        return;
                    }

                    const data = await res.json();
                    if (!data || data.message) {
                        return;
                    }

                    const map = {
                        street: targets.street,
                        district: targets.district,
                        city: targets.city,
                        state: targets.state,
                    };

                    Object.entries(map).forEach(([key, selector]) => {
                        if (!selector || typeof selector !== 'string') {
                            return;
                        }
                        const field = document.querySelector(selector);
                        if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
                            const val = data[key];
                            if (typeof val === 'string' && val !== '') {
                                field.value = val;
                                field.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }
                    });
                } catch {
                    /* rede indisponível — silencioso */
                }
            },
            { passive: true },
        );
    });
}
