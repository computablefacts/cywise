<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('theme::partials.head', ['seo' => ($seo ?? null) ])
    <!-- Used to add dark mode right away, adding here prevents any flicker -->
    <script>
        if (typeof(Storage) !== "undefined") {
            if(localStorage.getItem('theme') && localStorage.getItem('theme') == 'dark'){
                document.documentElement.classList.add('dark');
            }
        }
    </script>

    <!-- FastBootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/fastbootstrap@2.2.0/dist/css/fastbootstrap.min.css"
          rel="stylesheet"
          integrity="sha256-V6lu+OdYNKTKTsVFBuQsyIlDiRWiOmtC8VQ8Lzdm2i4="
          crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
            crossorigin="anonymous"></script>

    <!-- app-specific styles -->
    <link href="{{ asset('cywise/css/app.css') }}" rel="stylesheet">

    <!-- design system -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(["resources/themes/cywise/assets/css/ui.css"])

    <!-- page-specific styles -->
    @stack('styles')

</head>
<body x-data class="ui:min-h-screen ui:bg-canvas ui:font-sans ui:text-ink ui:antialiased @if(config('wave.dev_bar')){{ 'pb-5' }}@endif">

@include('theme::iframes._blueprintjs')
@include('theme::iframes._toaster')
<script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
@include('theme::iframes._json-rpc')

    {{--
      ┌─────────┬───────────────────────────┐
      │         │ topbar                    │
      │ sidebar ├───────────────────────────┤
      │         │ main (page slot)          │
      └─────────┴───────────────────────────┘
    --}}
    <x-app.sidebar />

    <div class="ui:flex ui:min-h-screen ui:flex-col ui:lg:pl-64">
        <x-app.topbar />
        <main class="ui:flex ui:flex-1 ui:flex-col">
            {{ $slot }}
        </main>
    </div>

    @if(!auth()->guest() && auth()->user()->hasChangelogNotifications())
        @include('theme::partials.changelogs')
    @endif

    <!-- app-specific scripts -->
    @include('theme::partials.footer-scripts')
    {{ $javascript ?? '' }}

    <!-- page-specific scripts -->
    @stack('scripts')

</body>
</html>

