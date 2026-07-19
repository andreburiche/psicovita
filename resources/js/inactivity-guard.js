/**
 * Detecção de inatividade + aviso com contagem regressiva (Alpine).
 * Usa timestamps absolutos (não só setTimeout) para sobreviver a throttle de abas em background.
 * Sincroniza atividade/logout entre abas via localStorage.
 */

const ACTIVITY_KEY = 'psiconecta_inactivity_activity';
const EXPIRE_KEY = 'psiconecta_inactivity_expire';

export function createInactivityGuardData(
    timeoutMinutes,
    warningSeconds,
    keepAliveUrl,
    expireUrl,
) {
    const minutes = Math.max(1, Number(timeoutMinutes) || 60);
    let warning = Math.max(1, Number(warningSeconds) || 60);
    const timeoutMs = minutes * 60 * 1000;
    // Aviso nunca pode engolir o timeout inteiro (senão o modal “some” e o backend desloga).
    warning = Math.min(warning, Math.max(1, minutes * 60 - 1));
    const warningMs = warning * 1000;

    return {
        showWarning: false,
        countdown: warning,
        timeoutMs,
        warningMs,
        keepAliveUrl: keepAliveUrl || '/keep-alive',
        expireUrl: expireUrl || '/logout-por-inatividade',
        lastActivityAt: Date.now(),
        tickTimer: null,
        activityThrottle: null,
        leaving: false,

        init() {
            this.markActivity({ broadcast: true });
            this.tickTimer = setInterval(() => this.evaluateIdle(), 1000);

            const onActivity = () => {
                if (this.showWarning || this.leaving) {
                    return;
                }
                if (this.activityThrottle) {
                    return;
                }
                this.activityThrottle = setTimeout(() => {
                    this.activityThrottle = null;
                }, 1000);
                this.markActivity({ broadcast: true });
            };

            ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((evt) => {
                window.addEventListener(evt, onActivity, { passive: true });
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.evaluateIdle();
                }
            });
            window.addEventListener('focus', () => this.evaluateIdle());
            window.addEventListener('storage', (event) => this.onStorage(event));
        },

        idleMs() {
            return Date.now() - this.lastActivityAt;
        },

        markActivity({ broadcast = false } = {}) {
            this.lastActivityAt = Date.now();
            if (broadcast) {
                this.broadcastActivity();
            }
            if (this.showWarning) {
                this.clearWarningUi();
            }
        },

        evaluateIdle() {
            if (this.leaving) {
                return;
            }

            const idle = this.idleMs();

            if (idle >= this.timeoutMs) {
                this.logout({ broadcast: true });
                return;
            }

            const warningAt = this.timeoutMs - this.warningMs;
            if (idle >= warningAt) {
                const remainingSec = Math.max(1, Math.ceil((this.timeoutMs - idle) / 1000));
                if (! this.showWarning) {
                    this.startWarning(remainingSec);
                } else {
                    this.countdown = remainingSec;
                }
            }
        },

        onStorage(event) {
            if (this.leaving) {
                return;
            }

            if (event.key === EXPIRE_KEY && event.newValue) {
                this.logout({ broadcast: false });
                return;
            }

            if (event.key === ACTIVITY_KEY && event.newValue) {
                const ts = Number(event.newValue);
                if (Number.isFinite(ts) && ts > this.lastActivityAt) {
                    this.lastActivityAt = ts;
                    this.clearWarningUi();
                }
            }
        },

        broadcastActivity() {
            try {
                localStorage.setItem(ACTIVITY_KEY, String(this.lastActivityAt));
            } catch (e) {
                // private mode / storage bloqueado
            }
        },

        broadcastExpire() {
            try {
                localStorage.setItem(EXPIRE_KEY, String(Date.now()));
            } catch (e) {
                // private mode / storage bloqueado
            }
        },

        clearWarningUi() {
            this.showWarning = false;
            document.body.classList.remove('overflow-y-hidden');
        },

        startWarning(remainingSec) {
            if (this.showWarning || this.leaving) {
                return;
            }

            this.showWarning = true;
            this.countdown = remainingSec ?? Math.round(this.warningMs / 1000);
            document.body.classList.add('overflow-y-hidden');
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        async keepAlive() {
            if (this.leaving) {
                return;
            }

            this.clearWarningUi();
            this.markActivity({ broadcast: true });

            try {
                await fetch(this.keepAliveUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
            } catch (e) {
                // Se o keep-alive falhar, o middleware ainda protege na próxima navegação.
            }
        },

        logout({ broadcast = true } = {}) {
            if (this.leaving) {
                return;
            }
            this.leaving = true;
            if (this.tickTimer) {
                clearInterval(this.tickTimer);
                this.tickTimer = null;
            }

            if (broadcast) {
                this.broadcastExpire();
            }

            window.location.href = this.expireUrl;
        },
    };
}
