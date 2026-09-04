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
    <link
        crossorigin="anonymous"
        href="https://cdn.jsdelivr.net/npm/fastbootstrap@2.2.0/dist/css/fastbootstrap.min.css"
        integrity="sha256-V6lu+OdYNKTKTsVFBuQsyIlDiRWiOmtC8VQ8Lzdm2i4="
        rel="stylesheet"
    >
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&amp;family=Space+Grotesk:wght@500;600;700&amp;display=swap"
        rel="stylesheet"
    >
    <link href="{{ asset('cywise/website-v2/styles.css') }}?v={{ filemtime(public_path('cywise/website-v2/styles.css')) }}" rel="stylesheet">
    @stack('website-v2-head')
</head>
<body>
    @include('theme::partials.website-v2.header', [
        'languageUrl' => $languageUrl,
        'locale' => $locale,
    ])

    {{ $slot }}

    @include('theme::partials.website-v2.footer', ['locale' => $locale])

    <script
        crossorigin="anonymous"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    ></script>
    <script src="{{ asset('cywise/website-v2/app.js') }}?v={{ filemtime(public_path('cywise/website-v2/app.js')) }}"></script>
    @stack('website-v2-scripts')
</body>
</html>
