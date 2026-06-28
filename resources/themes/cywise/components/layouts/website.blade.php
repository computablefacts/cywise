<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('theme::partials.head', ['seo' => ($seo ?? null) ])
</head>
<body x-data class="d-flex flex-column min-vh-100 overflow-x-hidden @if($bodyClass ?? false){{ $bodyClass }}@endif" x-cloak>

    <x-marketing.elements.header />

    <main class="flex-grow-1 overflow-x-hidden d-flex flex-column min-vh-0">
        {{ $slot }}
    </main>

    @livewire('notifications')
    @include('theme::partials.footer')
    @include('theme::partials.footer-scripts')
    {{ $javascript ?? '' }}

    <script>
      const elFooter = document.getElementsByTagName('footer')[0];
      if (elFooter) {
        elFooter.classList.remove('pt-10');
      }
    </script>
</body>
</html>
