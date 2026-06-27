<script>
    (function () {
        'use strict';
        window.psiApplyTheme = function (dark) {
            document.documentElement.classList.toggle('dark', dark === true);
        };
        window.psiToggleTheme = function () {
            var d = !document.documentElement.classList.contains('dark');
            localStorage.setItem('theme', d ? 'dark' : 'light');
            window.psiApplyTheme(d);
            try {
                window.dispatchEvent(new CustomEvent('psi-theme-changed', { detail: { dark: d } }));
            } catch (e) {}
        };
        try {
            var t = localStorage.getItem('theme');
            var dark = t === 'dark' || (t !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            window.psiApplyTheme(dark);
        } catch (e) {}
    })();
</script>
