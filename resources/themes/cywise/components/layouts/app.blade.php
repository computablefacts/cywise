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

    <!-- page-specific styles -->
    @stack('styles')

</head>
<body x-data class="d-flex flex-column min-vh-100 bg-light @if(config('wave.dev_bar')){{ 'pb-5' }}@endif">

@include('theme::iframes._blueprintjs')
@include('theme::iframes._toaster')
<script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
@include('theme::iframes._json-rpc')

    <x-app.sidebar />

    <style>
        @media (min-width: 992px) {
            .sidebar-margin {
                padding-left: 260px !important;
            }
        }
    </style>
    <div class="d-flex flex-column flex-grow-1 min-vh-100 ps-0 sidebar-margin">
        {{-- Mobile Header --}}
        <header class="d-lg-none px-3 d-flex justify-content-between sticky-top z-3 bg-light border-bottom h-72px align-items-center">
            <button x-on:click="window.dispatchEvent(new CustomEvent('open-sidebar'))" class="d-flex flex-shrink-0 justify-content-center align-items-center w-40px h-40px rounded text-dark hover-bg-light border-0 bg-transparent">
                <svg class="w-20px h-20px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg>
            </button>
            <x-app.user-menu position="top" />
        </header>
        {{-- End Mobile Header --}}
        <main class="d-flex flex-column flex-grow-1 ps-lg-0 min-vh-100 border-start-lg">
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

