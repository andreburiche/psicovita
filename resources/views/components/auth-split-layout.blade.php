@props([
    'heading' => __('Entrar'),
    'description' => __('Bem-vindo de volta. Introduza os seus dados.'),
    'promoteRegister' => true,
    'promoteLogin' => false,
])

@php
    $authPanelImage = config('app.auth_panel_image');
    $authPanelImageUrl = filled($authPanelImage)
        ? (is_string($authPanelImage) && (str_starts_with($authPanelImage, 'http://') || str_starts_with($authPanelImage, 'https://'))
            ? $authPanelImage
            : asset($authPanelImage))
        : null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }} — {{ $heading }}</title>

        @include('partials.fonts')
        @include('layouts.partials.theme-init')
        @include('layouts.partials.ui-accent-init')
        @include('partials.head-vite')
    </head>
    <body class="psi-app-background h-full font-sans text-slate-900 antialiased transition-colors duration-300 dark:text-slate-100">
        <x-theme-toggle variant="fixed" />
        <div class="grid min-h-full lg:grid-cols-2">
            {{-- Branding / ilustração (esquerda no desktop) --}}
            <div class="relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-between lg:p-12">
                {{-- Gradiente base da marca --}}
                <div class="absolute inset-0 bg-gradient-to-br from-violet-700 via-brand-700 to-indigo-900" aria-hidden="true"></div>

                @if ($authPanelImageUrl)
                    {{-- Imagem decorativa com opacidade --}}
                    <img
                        src="{{ $authPanelImageUrl }}"
                        alt=""
                        class="absolute inset-0 h-full w-full object-cover opacity-[0.28] mix-blend-luminosity"
                        loading="lazy"
                        decoding="async"
                        fetchpriority="low"
                    />
                @endif

                {{-- Overlay violeta para contraste e legibilidade do texto --}}
                <div class="absolute inset-0 bg-gradient-to-br from-violet-800/88 via-brand-700/82 to-indigo-950/92" aria-hidden="true"></div>

                {{-- Brilhos suaves --}}
                <div class="pointer-events-none absolute inset-0 opacity-25" aria-hidden="true">
                    <div class="absolute -left-20 top-20 h-72 w-72 rounded-full bg-white blur-3xl"></div>
                    <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-indigo-300 blur-3xl"></div>
                </div>

                <div class="relative z-10">
                    <a href="{{ route('home') }}" class="inline-flex rounded-xl py-1 text-white/90 outline-none ring-white/30 transition hover:text-white focus-visible:ring-2">
                        <x-psiconecta-logo variant="auth" :inverted="true" class="gap-3" />
                    </a>
                    <h1 class="mt-14 max-w-md text-4xl font-bold leading-tight tracking-tight text-white">
                        {{ __('Gestão clínica pensada para o consultório.') }}
                    </h1>
                    <p class="mt-4 max-w-md text-base leading-relaxed text-violet-100/90">
                        {{ __('Agenda, pacientes, sessões e LGPD num só lugar — visual limpo e foco no que importa.') }}
                    </p>
                </div>
                <p class="relative z-10 text-xs font-medium text-violet-200/70">
                    {{ __('Multi-profissional · Registos encriptados · API preparada') }}
                </p>
            </div>

            {{-- Formulário --}}
            <div class="relative flex flex-col justify-center px-4 py-12 sm:px-8 lg:px-16 xl:px-24 dark:bg-slate-950">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 lg:hidden">
                        <a href="{{ route('home') }}" class="relative inline-flex overflow-hidden rounded-2xl px-5 py-4 outline-none ring-violet-500/25 transition hover:opacity-95 focus-visible:ring-2">
                            <span class="absolute inset-0 bg-gradient-to-br from-violet-700 via-brand-700 to-indigo-900" aria-hidden="true"></span>
                            @if ($authPanelImageUrl)
                                <img
                                    src="{{ $authPanelImageUrl }}"
                                    alt=""
                                    class="absolute inset-0 h-full w-full object-cover opacity-25 mix-blend-luminosity"
                                    loading="lazy"
                                    decoding="async"
                                />
                            @endif
                            <span class="absolute inset-0 bg-gradient-to-br from-violet-800/90 via-brand-700/85 to-indigo-950/90" aria-hidden="true"></span>
                            <span class="relative z-10">
                                <x-psiconecta-logo variant="auth" :inverted="true" class="gap-2" />
                            </span>
                        </a>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white p-8 shadow-card dark:border-slate-700/80 dark:bg-slate-900/90 sm:p-10">
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $heading }}</h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>

                        <div class="mt-8">
                            {{ $slot }}
                        </div>
                    </div>

                    @if ($promoteRegister && Route::has('register'))
                        <p class="mt-8 text-center text-sm text-slate-600 dark:text-slate-400">
                            {{ __('Ainda não tem conta?') }}
                            <a href="{{ route('register') }}" class="font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400 dark:hover:text-violet-300">{{ __('Criar conta') }}</a>
                        </p>
                    @endif

                    @if ($promoteLogin && Route::has('login'))
                        <p class="mt-8 text-center text-sm text-slate-600 dark:text-slate-400">
                            {{ __('Já tem conta?') }}
                            <a href="{{ route('login') }}" class="font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400 dark:hover:text-violet-300">{{ __('Entrar') }}</a>
                        </p>
                    @endif

                    <p class="mt-6 text-center">
                        <a href="{{ route('home') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">{{ __('Voltar ao início') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
