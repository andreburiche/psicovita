/**
 * Guard de inatividade — Alpine via CDN (sem bundle Vite).
 * Manter alinhado a resources/js/inactivity-guard.js
 */
(function () {
    var ACTIVITY_KEY = 'psiconecta_inactivity_activity';
    var EXPIRE_KEY = 'psiconecta_inactivity_expire';

    function createInactivityGuardData(timeoutMinutes, warningSeconds, keepAliveUrl, expireUrl) {
        var minutes = Math.max(1, Number(timeoutMinutes) || 60);
        var warning = Math.max(1, Number(warningSeconds) || 60);
        var timeoutMs = minutes * 60 * 1000;
        warning = Math.min(warning, Math.max(1, minutes * 60 - 1));
        var warningMs = warning * 1000;

        return {
            showWarning: false,
            countdown: warning,
            timeoutMs: timeoutMs,
            warningMs: warningMs,
            keepAliveUrl: keepAliveUrl || '/keep-alive',
            expireUrl: expireUrl || '/logout-por-inatividade',
            lastActivityAt: Date.now(),
            tickTimer: null,
            activityThrottle: null,
            leaving: false,

            init() {
                this.markActivity({ broadcast: true });
                var self = this;
                this.tickTimer = setInterval(function () {
                    self.evaluateIdle();
                }, 1000);

                var onActivity = function () {
                    if (self.showWarning || self.leaving) {
                        return;
                    }
                    if (self.activityThrottle) {
                        return;
                    }
                    self.activityThrottle = setTimeout(function () {
                        self.activityThrottle = null;
                    }, 1000);
                    self.markActivity({ broadcast: true });
                };

                ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(function (evt) {
                    window.addEventListener(evt, onActivity, { passive: true });
                });

                document.addEventListener('visibilitychange', function () {
                    if (document.visibilityState === 'visible') {
                        self.evaluateIdle();
                    }
                });
                window.addEventListener('focus', function () {
                    self.evaluateIdle();
                });
                window.addEventListener('storage', function (event) {
                    self.onStorage(event);
                });
            },

            idleMs() {
                return Date.now() - this.lastActivityAt;
            },

            markActivity(options) {
                var broadcast = options && options.broadcast;
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

                var idle = this.idleMs();

                if (idle >= this.timeoutMs) {
                    this.logout({ broadcast: true });
                    return;
                }

                var warningAt = this.timeoutMs - this.warningMs;
                if (idle >= warningAt) {
                    var remainingSec = Math.max(1, Math.ceil((this.timeoutMs - idle) / 1000));
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
                    var ts = Number(event.newValue);
                    if (isFinite(ts) && ts > this.lastActivityAt) {
                        this.lastActivityAt = ts;
                        this.clearWarningUi();
                    }
                }
            },

            broadcastActivity() {
                try {
                    localStorage.setItem(ACTIVITY_KEY, String(this.lastActivityAt));
                } catch (e) {}
            },

            broadcastExpire() {
                try {
                    localStorage.setItem(EXPIRE_KEY, String(Date.now()));
                } catch (e) {}
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
                this.countdown = remainingSec != null ? remainingSec : Math.round(this.warningMs / 1000);
                document.body.classList.add('overflow-y-hidden');
            },

            csrfToken() {
                var meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            },

            keepAlive() {
                if (this.leaving) {
                    return;
                }

                this.clearWarningUi();
                this.markActivity({ broadcast: true });

                fetch(this.keepAliveUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }).catch(function () {});
            },

            logout(options) {
                var broadcast = ! options || options.broadcast !== false;
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

    document.addEventListener('alpine:init', function () {
        if (window.__psiconectaInactivityGuardRegistered) {
            return;
        }
        window.__psiconectaInactivityGuardRegistered = true;
        window.Alpine.data('inactivityGuard', createInactivityGuardData);
    });
})();
