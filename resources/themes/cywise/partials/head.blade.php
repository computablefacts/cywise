@php
    if(isset($seo)){
        $seo = (is_array($seo)) ? ((object)$seo) : $seo;
    }
    $title = isset($title) ? $title : (isset($seo->title) ? $seo->title : setting('site.description', 'Cybersecurity for the mortals'));
    $title = $title . ' | ' . setting('site.title', 'Cywise');
@endphp

<meta charset="utf-8">
<meta http-equiv="x-ua-compatible" content="ie=edge"> <!-- † -->
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="csrf-token" content="{{ csrf_token() }}">

<x-favicon></x-favicon>

@php
    $robots = (url('/') === 'https://www.cywise.io') ? 'index,follow' : 'noindex,nofollow';
    $canonical = preg_replace('/^http[s]?:\/\/(?:.*?)\.?cywise\.io/i', 'https://www.cywise.io', Request::fullUrl());
@endphp

<link rel="canonical" href="{{ $canonical }}">
<meta name="url" content="{{ $canonical }}">
<meta property="og:url" content="{{ $canonical }}">

<meta name="robots" content="{{ $robots }}">
<meta name="googlebot" content="{{ $robots }}">

<meta property="og:site_name" content="{{ setting('site.title', 'Cywise') }}">
<meta property="og:type" content="@if(isset($seo->type)){{ $seo->type }}@else{{ 'article' }}@endif">

@if(isset($title))
    <title>{{ $title }}</title>
    <meta itemprop="name" content="{{ $title }}">
    <meta property="og:title" content="{{ $title }}">
@endif
@if(isset($seo->image))
    <meta itemprop="image" content="{{ $seo->image }}">
    <meta property="og:image" content="{{ $seo->image }}">
@endif
@if(isset($seo->image_w) && isset($seo->image_h))
    <meta property="og:image:width" content="{{ $seo->image_w }}">
    <meta property="og:image:height" content="{{ $seo->image_h }}">
@endif
@if(isset($seo->description))
    <meta name="description" content="{{ $seo->description }}">
    <meta itemprop="description" content="{{ $seo->description }}">
    <meta property="og:description" content="{{ $seo->description }}">
@endif

@filamentStyles
@livewireStyles
@vite(["resources/themes/cywise/assets/css/app.css", "resources/themes/cywise/assets/js/app.js"])
