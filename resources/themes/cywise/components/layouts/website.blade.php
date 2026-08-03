<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('theme::partials.head', ['seo' => ($seo ?? null) ])
</head>
<body x-data class="flex flex-col overflow-x-hidden @if($bodyClass ?? false){{ $bodyClass }}@endif" x-cloak style="min-height:100vh;">

    <x-marketing.elements.header />

    <main class="flex flex-col grow overflow-x-hidden">
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
