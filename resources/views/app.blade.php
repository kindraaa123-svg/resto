<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="app-url" content="{{ config('app.url') }}">

        <style>
            html {
                background-color: oklch(1 0 0);
            }
        </style>

        @php
            $systemLogo = isset($page['props']['system']['logo']) && $page['props']['system']['logo'] 
                ? $page['props']['system']['logo'] 
                : asset('favicon.ico');
        @endphp

        <link rel="icon" href="{{ $systemLogo }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ $systemLogo }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
