export function createAppShellData() {
    return {
        sidebarOpen: false,
        sidebarCollapsed: false,
        isDesktop: typeof window !== 'undefined' && window.matchMedia('(min-width: 1024px)').matches,
        sidebarLastFocus: null,

        init() {
            try {
                this.sidebarCollapsed = localStorage.getItem('psiconecta_sidebar_collapsed') === '1';
            } catch (e) {}

            const mq = window.matchMedia('(min-width: 1024px)');
            this.isDesktop = mq.matches;
            mq.addEventListener('change', (e) => {
                this.isDesktop = e.matches;
                if (e.matches) {
                    this.closeSidebar(false);
                }
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && this.sidebarOpen && ! this.isDesktop) {
                    this.closeSidebar();
                }
            });
        },

        toggleSidebarCollapse() {
            this.sidebarCollapsed = ! this.sidebarCollapsed;
            try {
                localStorage.setItem('psiconecta_sidebar_collapsed', this.sidebarCollapsed ? '1' : '0');
            } catch (e) {}
        },

        navLabelsVisible() {
            return ! this.sidebarCollapsed || ! this.isDesktop;
        },

        openSidebar() {
            if (this.isDesktop) {
                return;
            }

            this.sidebarLastFocus = document.activeElement;
            this.sidebarOpen = true;
            document.body.classList.add('overflow-y-hidden');

            this.$nextTick(() => {
                const first = this.sidebarFocusables()[0];
                first?.focus();
            });
        },

        closeSidebar(restoreFocus = true) {
            this.sidebarOpen = false;
            document.body.classList.remove('overflow-y-hidden');

            if (restoreFocus && this.sidebarLastFocus?.focus) {
                this.sidebarLastFocus.focus();
            }
        },

        sidebarFocusables() {
            const aside = document.getElementById('app-sidebar');
            if (! aside) {
                return [];
            }

            const selector = 'a, button, input:not([type=\'hidden\']), textarea, select, [tabindex]:not([tabindex=\'-1\'])';

            return [...aside.querySelectorAll(selector)].filter((el) => ! el.hasAttribute('disabled'));
        },

        trapSidebarTab(event) {
            if (! this.sidebarOpen || this.isDesktop) {
                return;
            }

            const focusables = this.sidebarFocusables();
            if (focusables.length === 0) {
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const active = document.activeElement;

            if (event.shiftKey && active === first) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && active === last) {
                event.preventDefault();
                first.focus();
            }
        },
    };
}
