<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('theme::partials.head', ['seo' => ($seo ?? null) ])
</head>
<body x-data class="flex flex-col overflow-x-hidden @if($bodyClass ?? false){{ $bodyClass }}@endif" x-cloak style="min-height:100vh;">

    <x-marketing.elements.header />

    <main class="flex flex-col grow overflow-x-hidden">
      <iframe src="{{ route('iframes.changelog2') }}" class="w-full flex-1 min-h-0 border-0"></iframe>
    </main>

    @livewire('notifications')
    @include('theme::partials.footer')
    @include('theme::partials.footer-scripts')
    {{ $javascript ?? '' }}

</body>
</html>
