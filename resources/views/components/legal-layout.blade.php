@props([
    'title' => __('Documento legal'),
    'active' => null,
    'description' => null,
    'updatedAt' => null,
    'sections' => [],
    'icon' => 'document-text',
    'badge' => null,
])

@php
    $company = config('compliance.lgpd.company_name');
    $dpoEmail = config('compliance.lgpd.dpo_email');
    $dpoName = config('compliance.lgpd.dpo_name');
    $updatedLabel = $updatedAt ?? now()->format('d/m/Y');

    $navItems = [
        'privacy' => ['route' => 'legal.privacy', 'label' => __('Privacidade'), 'icon' => 'shield-check'],
        'terms' => ['route' => 'legal.terms', 'label' => __('Termos'), 'icon' => 'document-text'],
        'dpia-ai' => ['route' => 'legal.dpia-ai', 'label' => __('DPIA IA'), 'icon' => 'sparkles'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description ?? $title }} — {{ config('app.name') }}">
        <title>{{ $title }} — {{ config('app.name') }}</title>
        @include('partials.fonts')
        @include('layouts.partials.theme-init')
        @include('layouts.partials.ui-accent-init')
        @include('partials.head-vite')
    </head>
    <body class="psi-app-background min-h-full font-sans text-slate-800 antialiased dark:text-slate-100">
        <x-skip-link />

        {{-- Cabeçalho --}}
        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-md dark:border-slate-700/80 dark:bg-slate-900/90">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <a
                    href="{{ route('home') }}"
                    class="flex min-w-0 shrink items-center gap-2 rounded-xl py-1 outline-none transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-violet-500/40"
                    aria-label="{{ __('Voltar ao início') }} — {{ config('app.name') }}"
                >
                    <x-psiconecta-logo variant="topbar" class="h-8" />
                </a>

                <nav class="hidden items-center gap-1 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-1 dark:border-slate-700 dark:bg-slate-800/60 sm:flex" aria-label="{{ __('Documentos legais') }}">
                    @foreach ($navItems as $key => $item)
                        <a
                            href="{{ route($item['route']) }}"
                            @if ($active === $key) aria-current="page" @endif
                            @class([
                                'inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold transition sm:text-sm',
                                'bg-white text-violet-700 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:text-violet-300 dark:ring-slate-600' => $active === $key,
                                'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-700/60 dark:hover:text-slate-200' => $active !== $key,
                            ])
                        >
                            <x-ui.icon :name="$item['icon']" class="h-4 w-4 shrink-0 opacity-80" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="flex shrink-0 items-center gap-2">
                    <x-theme-toggle />
                    @auth
                        <a
                            href="{{ route(auth()->user()->defaultAppRouteName()) }}"
                            class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-violet-200 hover:text-violet-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-violet-600 sm:inline-flex"
                        >
                            {{ __('Ir para a app') }}
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="hidden rounded-xl bg-violet-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-500 sm:inline-flex"
                        >
                            {{ __('Entrar') }}
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Nav mobile --}}
            <nav class="flex gap-2 overflow-x-auto border-t border-slate-100 px-4 py-2 dark:border-slate-800 sm:hidden" aria-label="{{ __('Documentos legais') }}">
                @foreach ($navItems as $key => $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @if ($active === $key) aria-current="page" @endif
                        @class([
                            'inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold',
                            'bg-violet-600 text-white' => $active === $key,
                            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => $active !== $key,
                        ])
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </header>

        {{-- Hero --}}
        <div class="relative overflow-hidden border-b border-violet-200/40 bg-gradient-to-br from-violet-600 via-indigo-600 to-violet-800 dark:border-violet-900/50">
            <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-indigo-400/20 blur-3xl" aria-hidden="true"></div>
            <div class="relative mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:gap-6">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-white shadow-lg ring-1 ring-white/20 backdrop-blur-sm" aria-hidden="true">
                        <x-ui.icon :name="$icon" class="h-7 w-7" />
                    </span>
                    <div class="min-w-0 flex-1">
                        @if ($badge)
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-violet-200">{{ $badge }}</p>
                        @endif
                        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl lg:text-4xl">{{ $title }}</h1>
                        @if ($description)
                            <p class="mt-3 max-w-3xl text-sm leading-relaxed text-violet-100/90 sm:text-base">{{ $description }}</p>
                        @endif
                        <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-violet-100 ring-1 ring-white/15 backdrop-blur-sm">
                            <x-ui.icon name="clock" class="h-3.5 w-3.5 opacity-80" />
                            {{ __('Última atualização:') }} {{ $updatedLabel }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-12 lg:gap-10">
                @if (count($sections) > 0)
                    <aside class="lg:col-span-4 xl:col-span-3">
                        <nav
                            class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60 lg:sticky lg:top-24"
                            aria-label="{{ __('Índice do documento') }}"
                        >
                            <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <x-ui.icon name="clipboard-list" class="h-4 w-4" />
                                {{ __('Neste documento') }}
                            </p>
                            <ol class="mt-3 space-y-1 text-sm">
                                @foreach ($sections as $section)
                                    <li>
                                        <a
                                            href="#{{ $section['id'] }}"
                                            class="block rounded-lg px-3 py-2 font-medium text-slate-600 transition hover:bg-violet-50 hover:text-violet-700 dark:text-slate-300 dark:hover:bg-violet-950/40 dark:hover:text-violet-300"
                                        >
                                            {{ $section['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    </aside>
                @endif

                <main id="main-content" @class(['min-w-0', 'lg:col-span-8 xl:col-span-9' => count($sections) > 0, 'lg:col-span-12' => count($sections) === 0]) tabindex="-1">
                    <article class="space-y-8 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60 sm:p-8 lg:p-10">
                        {{ $slot }}
                    </article>

                    {{-- Contacto DPO --}}
                    <aside class="mt-8">
                        <x-legal.callout variant="contact" :title="__('Dúvidas sobre privacidade ou LGPD?')">
                            <p>
                                {{ __('Encarregado (DPO):') }} <strong>{{ $dpoName }}</strong><br>
                                {{ __('Empresa:') }} {{ $company }}
                            </p>
                            <p class="mt-2">
                                <a href="mailto:{{ $dpoEmail }}">{{ $dpoEmail }}</a>
                            </p>
                            @auth
                                @if (auth()->user()->usesPatientPortalExperience())
                                    <p class="mt-3">
                                        <a href="{{ route('patient.lgpd.index') }}">{{ __('Aceder ao portal de privacidade') }} →</a>
                                    </p>
                                @endif
                            @endauth
                        </x-legal.callout>
                    </aside>
                </main>
            </div>
        </div>

        <footer class="border-t border-slate-200 bg-white py-8 dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 text-center sm:flex-row sm:px-6 lg:px-8 sm:text-left">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    &copy; {{ date('Y') }} {{ $company }} · {{ config('app.name') }}
                </p>
                <nav class="flex flex-wrap justify-center gap-4 text-xs font-semibold text-slate-600 dark:text-slate-400" aria-label="{{ __('Links legais') }}">
                    @foreach ($navItems as $key => $item)
                        <a href="{{ route($item['route']) }}" class="transition hover:text-violet-600 dark:hover:text-violet-400">{{ $item['label'] }}</a>
                    @endforeach
                    <a href="{{ route('home') }}" class="transition hover:text-violet-600 dark:hover:text-violet-400">{{ __('Início') }}</a>
                </nav>
            </div>
        </footer>

        <x-site-public-widgets />

        <x-confirm-dialog />
    </body>
</html>
