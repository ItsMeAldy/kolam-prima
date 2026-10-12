<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $settings->brand_name . ' — ' . $settings->tagline)</title>
    <meta name="description" content="@yield('description', $settings->tagline)">
    <meta name="theme-color" content="#0284c7">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $settings->brand_name }}">
    <meta property="og:title" content="@yield('title', $settings->brand_name . ' — ' . $settings->tagline)">
    <meta property="og:description" content="@yield('description', $settings->tagline)">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-navy-900 antialiased">
    @yield('content')

    @stack('scripts')
</body>
</html>
