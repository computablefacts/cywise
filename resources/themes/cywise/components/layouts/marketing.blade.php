<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('theme::partials.head', ['seo' => ($seo ?? null) ])
</head>
<body x-data class="d-flex flex-column min-vh-100 overflow-x-hidden @if($bodyClass ?? false){{ $bodyClass }}@endif" x-cloak>

    <x-marketing.elements.header />

    <main class="flex-grow-1 overflow-x-hidden">
        {{ $slot }}
    </main>

    @livewire('notifications')
    @include('theme::partials.footer')
    @include('theme::partials.footer-scripts')
    {{ $javascript ?? '' }}

</body>
</html>
