<style id="psi-ui-accent-styles">
{!! \App\Support\UiAccentOptions::cssBlock() !!}
</style>
<script>
    (function () {
        'use strict';
        window.psiApplyAccent = function (accent) {
            var value = accent || @json(\App\Support\UiAccentOptions::DEFAULT);
            document.documentElement.setAttribute('data-ui-accent', value);
            try {
                localStorage.setItem('psi-ui-accent', value);
            } catch (e) {}
            try {
                window.dispatchEvent(new CustomEvent('psi-accent-changed', { detail: { accent: value } }));
            } catch (e) {}
        };

        var serverAccent = @json(auth()->check() ? auth()->user()->resolvedUiAccent() : null);
        var storedAccent = null;
        try {
            storedAccent = localStorage.getItem('psi-ui-accent');
        } catch (e) {}

        window.psiApplyAccent(serverAccent || storedAccent || @json(\App\Support\UiAccentOptions::DEFAULT));
    })();
</script>
