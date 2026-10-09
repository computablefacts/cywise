@props([
    'languageUrl' => null,
    'locale' => 'fr',
    'seo' => [],
])

@php
    $canonical = preg_replace(
        '/^http[s]?:\/\/(?:.*?)\.?cywise\.io/i',
        'https://www.cywise.io',
        request()->fullUrl(),
    ) ?? request()->fullUrl();
    $description = $seo['description'] ?? '';
    $robots = url('/') === 'https://www.cywise.io' ? 'index,follow' : 'noindex,nofollow';
    $title = $seo['title'] ?? 'Cywise';
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta content="{{ csrf_token() }}" name="csrf-token">
    <title>{{ $title }}</title>
    <meta content="{{ $description }}" name="description">
    <meta content="{{ $robots }}" name="robots">
    <meta content="{{ $robots }}" name="googlebot">
    <link href="{{ $canonical }}" rel="canonical">
    <meta content="{{ $canonical }}" name="url">
    <meta content="{{ $canonical }}" property="og:url">
    <meta content="Cywise" property="og:site_name">
    <meta content="{{ $seo['type'] ?? 'website' }}" property="og:type">
    <meta content="{{ $title }}" property="og:title">
    <meta content="{{ $description }}" property="og:description">

    @if (isset($seo['image']))
        <meta content="{{ $seo['image'] }}" property="og:image">
    @endif

    @if ($languageUrl)
        <link href="{{ $languageUrl }}" hreflang="{{ $locale === 'en' ? 'fr' : 'en' }}" rel="alternate">
    @endif

    <link href="{{ asset('favicon.ico') }}" rel="icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400;62..125,500;62..125,600;62..125,700;62..125,800;62..125,900&amp;family=DM+Mono:wght@400;500&amp;family=Inter:wght@400;500;600&amp;display=swap"
        rel="stylesheet"
    >
    {{-- Same design system as the app: tokens and "ui:" utilities --}}
    @vite(['resources/themes/cywise/assets/css/ui.css'])
    {{-- ui.css has no preflight (the app relies on Bootstrap's reboot): minimal reset for the website --}}
    <style>
        *, ::before, ::after { box-sizing: border-box; }
        img, svg { display: block; max-width: 100%; }
        button, input { font: inherit; }
        html { scroll-behavior: smooth; }
    </style>
    @stack('website-v2-head')
</head>
<body class="ui:m-0 ui:bg-white ui:font-display ui:text-ink ui:antialiased">
    @include('theme::partials.website-v2.header', [
        'languageUrl' => $languageUrl,
        'locale' => $locale,
    ])

    {{ $slot }}

    @include('theme::partials.website-v2.footer', ['locale' => $locale])

    @stack('website-v2-scripts')
</body>
</html>
