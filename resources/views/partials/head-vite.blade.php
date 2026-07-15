@php
    $viteEntries = $viteEntries ?? ['resources/css/app.css', 'resources/js/app.js'];
    $hasViteBuild = file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json'));
@endphp
@if ($hasViteBuild)
    @vite($viteEntries)
@else
    {{-- Fallback sem Vite (local ou hospedagem sem public/build): Tailwind browser + Alpine CDN. --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    {{-- Não usar @import "tailwindcss" aqui: o navegador resolve como URL relativa (/tailwindcss → 404).
         O @tailwindcss/browser compila utilities a partir deste bloco; ver health-up.blade.php no Laravel. --}}
    <style type="text/tailwindcss">
        @theme {
            --font-sans: Inter, ui-sans-serif, system-ui, sans-serif;
            --color-brand-50: #f5f3ff;
            --color-brand-100: #ede9fe;
            --color-brand-500: #7c3aed;
            --color-brand-600: #6d28d9;
            --color-brand-700: #5b21b6;
            --color-brand-900: #4c1d95;
            --color-brand-950: #2e1065;
            --shadow-soft: 0 1px 3px 0 rgb(0 0 0 / 0.06), 0 1px 2px -1px rgb(0 0 0 / 0.06);
            --shadow-card: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
        }

        @custom-variant dark (&:where(.dark, .dark *));

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
        .focus\:not-sr-only:focus {
            position: fixed;
            width: auto;
            height: auto;
            padding: 0.625rem 1rem;
            margin: 0;
            overflow: visible;
            clip: auto;
            white-space: normal;
        }

        [x-cloak] {
            display: none !important;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
    {{-- Registra componentes Alpine antes do CDN; sem Vite o app.js não carrega. --}}
    <script src="{{ asset('js/anamnesis-builder-alpine.js') }}"></script>
    <script src="{{ asset('js/confirm-dialog-alpine.js') }}"></script>
    <script src="{{ asset('js/app-shell-alpine.js') }}"></script>
    <script src="{{ asset('js/avatar-editor-alpine.js') }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js" crossorigin="anonymous"></script>
    <script defer src="{{ asset('js/form-widgets-fallback.js') }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js" crossorigin="anonymous"></script>
@endif
