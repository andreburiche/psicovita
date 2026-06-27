<!DOCTYPE html>
<html lang="pt" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ config('app.name') }} — gestão de consultas e utentes para profissionais de saúde mental.">

        <title>{{ config('app.name') }} — {{ __('Início') }}</title>

        @include('partials.fonts')
        @include('layouts.partials.theme-init')
        @include('partials.head-vite')
    </head>
    <body class="min-h-screen bg-gradient-to-b from-sky-50 via-white to-sky-50/30 font-sans text-slate-900 antialiased dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:text-slate-100">
        <x-skip-link />
        {{-- Barra de navegação --}}
        <header class="px-4 pt-4 sm:px-6 lg:px-8">
            <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 rounded-2xl border border-sky-200/70 bg-sky-100/60 px-4 py-3 shadow-sm backdrop-blur-md dark:border-slate-600 dark:bg-slate-800/95 dark:shadow-lg dark:shadow-black/20 sm:px-6" aria-label="{{ __('Navegação principal') }}">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center rounded-xl py-1 outline-none ring-sky-500/25 transition hover:opacity-90 focus-visible:ring-2">
                    <x-psiconecta-logo variant="landing" />
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    <a href="#top" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-sky-200/80">{{ __('Início') }}</a>
                    <a href="#funcionalidades" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-white/60 hover:text-slate-900">{{ __('Funcionalidades') }}</a>
                    <a href="#precos" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-white/60 hover:text-slate-900">{{ __('Preços') }}</a>
                    <a href="#sobre" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-white/60 hover:text-slate-900">{{ __('Sobre') }}</a>
                    <a href="#contato" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-white/60 hover:text-slate-900">{{ __('Contacto') }}</a>
                </div>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <x-theme-toggle />
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 transition hover:text-slate-900 sm:inline">{{ __('Criar conta') }}</a>
                    @endif
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-sky-500/25 transition hover:bg-sky-400"
                    >
                        <span class="hidden h-8 w-8 overflow-hidden rounded-full ring-2 ring-white/40 sm:block" aria-hidden="true">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=96&q=80" alt="" class="h-full w-full object-cover" width="32" height="32" loading="lazy" />
                        </span>
                        <span>{{ __('Entrar') }}</span>
                    </a>
                </div>
            </nav>
        </header>

        <main id="main-content" class="relative overflow-x-hidden" tabindex="-1">
            <div id="top" class="sr-only" aria-hidden="true"></div>
            {{-- Hero: modelo em duas colunas; coluna visual num cartão branco com prova social contida --}}
            <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20" data-test="landing-hero">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div class="order-2 lg:order-1">
                        <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight text-slate-900 dark:text-slate-100 sm:text-5xl lg:text-[3.25rem]">
                            {{ __('Simplifique o agendamento das consultas') }}
                            <span class="text-sky-500 dark:text-sky-400">{{ __('para um cuidado de excelência') }}</span>
                        </h1>
                        <p class="mt-6 max-w-xl text-base leading-relaxed text-slate-600 dark:text-slate-400 sm:text-lg">
                            {{ __('Organize sessões, utentes e pagamentos num só lugar. Menos tarefas administrativas, mais tempo para quem precisa de si — com acesso cómodo e seguro à sua prática.') }}
                        </p>
                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl bg-sky-500 px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-sky-500/30 transition hover:bg-sky-400">
                                {{ __('Começar agora') }}
                            </a>
                            <a href="#funcionalidades" class="inline-flex items-center justify-center rounded-2xl border-2 border-slate-900/10 bg-white px-8 py-3.5 text-base font-semibold text-slate-900 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">
                                {{ __('Saber mais') }}
                            </a>
                        </div>
                    </div>

                    <div class="order-1 lg:order-2">
                        <div class="relative overflow-hidden rounded-[2rem] border border-sky-200/80 bg-white p-4 shadow-xl shadow-sky-900/10 ring-1 ring-slate-200/60 dark:border-slate-600 dark:bg-slate-900/95 dark:ring-slate-600 sm:p-5 lg:p-6">
                            {{-- Prova social: sempre no fluxo, alinhada, sem margem negativa para fora do cartão --}}
                            <div class="mb-5 flex flex-nowrap items-center gap-4 sm:mb-6 sm:gap-5">
                                <div class="flex shrink-0 items-center ps-1">
                                    @foreach ([
                                        'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=80&q=80',
                                        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=80&q=80',
                                        'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=80&q=80',
                                        'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=80&q=80',
                                    ] as $i => $src)
                                        <img
                                            src="{{ $src }}"
                                            alt=""
                                            @class([
                                                'h-10 w-10 rounded-full border-2 border-white object-cover shadow-md ring-1 ring-slate-200/80 dark:border-slate-700 dark:ring-slate-600',
                                                'relative z-[1]' => $i === 0,
                                                'relative z-[2] -ms-2.5' => $i === 1,
                                                'relative z-[3] -ms-2.5' => $i === 2,
                                                'relative z-[4] -ms-2.5' => $i === 3,
                                            ])
                                            width="40"
                                            height="40"
                                            loading="lazy"
                                        />
                                    @endforeach
                                </div>
                                <div class="min-w-0 border-l border-sky-200/80 pl-4 dark:border-slate-600">
                                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Avaliações') }}</p>
                                    <p class="text-base font-bold leading-tight text-slate-900 dark:text-white">{{ __('5 em 5') }}</p>
                                </div>
                            </div>

                            <div class="flex gap-3 sm:gap-4">
                                <div class="relative min-w-0 flex-1">
                                    <img
                                        src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=640&q=80"
                                        alt="{{ __('Profissional de saúde') }}"
                                        class="aspect-[3/4] w-full rounded-3xl object-cover shadow-lg ring-1 ring-slate-200/80 dark:ring-slate-600"
                                        width="480"
                                        height="640"
                                        loading="eager"
                                    />
                                </div>
                                <div class="flex w-[30%] max-w-[132px] shrink-0 flex-col gap-3 sm:max-w-[148px] sm:gap-4">
                                    <img
                                        src="{{ asset('images/landing/hero-secondary-1.jpg') }}"
                                        alt="{{ __('Consulta terapêutica') }}"
                                        class="aspect-[4/5] w-full rounded-2xl object-cover shadow-md ring-1 ring-slate-200/80 dark:ring-slate-600"
                                        width="200"
                                        height="250"
                                        loading="lazy"
                                    />
                                    <img
                                        src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&w=320&q=80"
                                        alt=""
                                        class="aspect-[4/5] w-full rounded-2xl object-cover shadow-md ring-1 ring-slate-200/80 dark:ring-slate-600"
                                        width="200"
                                        height="250"
                                        loading="lazy"
                                    />
                                </div>
                            </div>

                            <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-5 dark:border-slate-700 sm:mt-6 sm:flex-row sm:items-center sm:justify-between sm:pt-6">
                                <div class="flex min-w-0 items-center gap-2 text-slate-800 dark:text-slate-200">
                                    <span class="shrink-0 text-amber-400" aria-hidden="true">★★★★★</span>
                                    <p class="text-sm font-medium leading-snug">{{ __('Classificação máxima em plataformas de confiança') }}</p>
                                </div>
                                <a href="#funcionalidades" class="shrink-0 text-sm font-semibold text-sky-600 transition hover:text-sky-500 dark:text-sky-400">{{ __('Explorar funcionalidades') }} →</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Redes (desktop, fixo à direita da viewport) --}}
            <div class="pointer-events-none fixed right-3 top-1/2 z-20 hidden -translate-y-1/2 lg:block" aria-hidden="true">
                <div class="pointer-events-auto flex flex-col gap-3 rounded-full border border-sky-200/80 bg-white/90 px-2 py-4 shadow-lg backdrop-blur-sm">
                    <a href="#" class="rounded-full p-2 text-slate-500 transition hover:bg-sky-50 hover:text-sky-600" aria-label="Instagram">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href="#" class="rounded-full p-2 text-slate-500 transition hover:bg-sky-50 hover:text-sky-600" aria-label="LinkedIn">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                </div>
            </div>

            {{-- Funcionalidades --}}
            <section id="funcionalidades" class="border-t border-sky-100/80 bg-white/50 py-16 sm:py-20">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">{{ __('Tudo o que precisa para gerir a sua prática') }}</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-center text-slate-600">{{ __('Agenda, utentes, sessões, pagamentos e prontuário — integrados numa experiência pensada para profissionais.') }}</p>
                    <ul class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            ['title' => __('Agenda inteligente'), 'body' => __('Visualização mensal, bloqueios e deteção de conflitos ao marcar sessões.')],
                            ['title' => __('Utentes e sessões'), 'body' => __('Histórico claro, estados de sessão e ligação direta ao financeiro.')],
                            ['title' => __('Segurança e LGPD'), 'body' => __('Dados clínicos com acesso controlado e autenticação moderna.')],
                        ] as $card)
                            <li class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-soft">
                                <h3 class="text-lg font-bold text-slate-900">{{ $card['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $card['body'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            @isset($plans)
                @include('landing.partials.pricing', ['plans' => $plans, 'trialDays' => $trialDays ?? 14])
            @endisset

            {{-- Sobre --}}
            <section id="sobre" class="py-16 sm:py-20">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="rounded-[2rem] border border-sky-200/60 bg-gradient-to-br from-sky-50 to-white p-8 shadow-card sm:p-12 lg:flex lg:items-center lg:gap-12">
                        <div class="lg:w-1/2">
                            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('O que é o :name?', ['name' => config('app.name')]) }}</h2>
                            <p class="mt-4 text-slate-600 leading-relaxed">
                                {{ __('O :name é uma plataforma para psicólogos e terapeutas que querem digitalizar marcações, finanças e notas clínicas sem perder o foco no utente. Desenvolvido com boas práticas de segurança e uma interface clara, para o dia a dia do consultório.', ['name' => config('app.name')]) }}
                            </p>
                        </div>
                        <div class="mt-8 flex justify-center lg:mt-0 lg:w-1/2">
                            <div class="grid max-w-md grid-cols-2 gap-4">
                                <div class="rounded-3xl bg-sky-500 p-6 text-white shadow-lg">
                                    <p class="text-3xl font-bold">24/7</p>
                                    <p class="mt-1 text-sm text-sky-100">{{ __('Acesso à sua conta') }}</p>
                                </div>
                                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-soft">
                                    <p class="text-3xl font-bold text-slate-900">100%</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ __('Focado na sua prática') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if ($partners->isNotEmpty())
                {{-- Parceiros / confiança --}}
                <section class="px-4 pb-20 sm:px-6 lg:px-8" aria-label="{{ __('Confiança') }}" data-test="landing-partners">
                    <div class="relative mx-auto max-w-6xl">
                        <div class="absolute left-1/2 top-0 z-10 h-4 w-28 -translate-x-1/2 -translate-y-1/2 rounded-b-2xl border border-t-0 border-sky-200 bg-white shadow-sm dark:border-slate-600 dark:bg-slate-900"></div>
                        <div class="rounded-[2rem] border-2 border-sky-200/80 bg-white px-6 py-12 shadow-soft dark:border-slate-600 dark:bg-slate-900/95 sm:px-10">
                            <p class="text-center text-xs font-semibold uppercase tracking-widest text-slate-400">{{ __('Confiança e parcerias') }}</p>
                            <div class="mt-8 flex flex-wrap items-center justify-center gap-x-10 gap-y-8 opacity-70 grayscale transition hover:opacity-90">
                                @foreach ($partners as $partner)
                                    @if (filled($partner->url))
                                        <a
                                            href="{{ $partner->url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center justify-center transition hover:opacity-100 hover:grayscale-0"
                                            title="{{ $partner->name }}"
                                        >
                                            @include('landing.partials.partner-item', ['partner' => $partner])
                                        </a>
                                    @else
                                        <span class="inline-flex items-center justify-center">
                                            @include('landing.partials.partner-item', ['partner' => $partner])
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Contacto --}}
            <section id="contato" class="border-t border-sky-100 bg-slate-50/80 py-14">
                <div class="mx-auto max-w-6xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-slate-900">{{ __('Pronto para simplificar o seu consultório?') }}</h2>
                    <p class="mx-auto mt-2 max-w-lg text-slate-600">{{ __('Crie a sua conta ou entre para explorar o painel.') }}</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-4">
                        <a href="{{ route('login') }}" class="inline-flex rounded-2xl bg-sky-500 px-8 py-3 font-semibold text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400">{{ __('Entrar') }}</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex rounded-2xl border-2 border-slate-200 bg-white px-8 py-3 font-semibold text-slate-800 transition hover:border-slate-300">{{ __('Criar conta') }}</a>
                        @endif
                    </div>
                </div>
            </section>
        </main>

        <x-site-public-widgets :site-context="$siteContext" />

        <footer class="border-t border-slate-200 bg-white py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
            <nav class="mb-3 flex flex-wrap items-center justify-center gap-4 text-sm" aria-label="{{ __('Links legais') }}">
                <a href="{{ route('legal.privacy') }}" class="font-medium text-violet-600 hover:underline dark:text-violet-400">{{ __('Política de Privacidade') }}</a>
                <a href="{{ route('legal.dpia-ai') }}" class="font-medium text-violet-600 hover:underline dark:text-violet-400">{{ __('DPIA IA') }}</a>
                <a href="{{ route('legal.terms') }}" class="font-medium text-violet-600 hover:underline dark:text-violet-400">{{ __('Termos de Uso') }}</a>
                <a href="mailto:{{ config('compliance.lgpd.dpo_email') }}" class="font-medium text-slate-600 hover:underline dark:text-slate-400">{{ __('Contato LGPD') }}</a>
            </nav>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('Todos os direitos reservados.') }}</p>
        </footer>
    </body>
</html>
