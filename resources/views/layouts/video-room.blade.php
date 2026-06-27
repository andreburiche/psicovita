<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? __('Sessão por vídeo') }} · {{ config('app.name') }}</title>
        @include('partials.fonts')
        @include('layouts.partials.theme-init')
        @include('partials.head-vite')
        @stack('head')
    </head>
    <body class="h-full bg-slate-950 font-sans text-slate-100 antialiased">
        @yield('content')
        @stack('scripts')
    </body>
</html>
