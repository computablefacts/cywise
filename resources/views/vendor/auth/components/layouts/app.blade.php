{{--
  Auth pages (login, register, password, verify, 2FA): same look as the website.

    ┌───────────────────────┬──────────────────┐
    │ ink panel             │ form ($slot)     │  lg+
    │ CYWISE + slogan       │                  │
    └───────────────────────┴──────────────────┘
    < lg: strip + wordmark above the form

  Overrides vendor/devdojo/auth. Its head stays: Livewire/Alpine assets and the package classes its pages still use.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- Page titles come from config('devdojo.auth.language'), in English --}}
    @include('auth::includes.head', ['title' => __($title ?? 'Auth')])
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400;62..125,700;62..125,800;62..125,900&amp;family=DM+Mono:wght@400;500&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet">
    @vite(['resources/themes/cywise/assets/css/ui.css'])
</head>
<body id="auth-body" class="ui:m-0 ui:bg-white ui:font-sans ui:text-ink ui:antialiased">
    @php
        $dyanicPageId = str_replace('/', '-', str_replace('.', '', Request::path()));
    @endphp
    <div x-data data-auth="{{ $dyanicPageId }}" class="ui:grid ui:min-h-screen ui:grid-cols-1 ui:lg:grid-cols-[minmax(0,1fr)_minmax(0,480px)] ui:xl:grid-cols-[minmax(0,1fr)_minmax(0,560px)]" x-cloak>

        {{-- Brand panel --}}
        <aside class="ui:hidden ui:flex-col ui:bg-ink ui:text-white ui:lg:flex">
            <x-site.strip/>
            <div class="ui:flex ui:flex-1 ui:flex-col ui:justify-between ui:gap-12 ui:p-12 ui:xl:p-16">
                <a href="{{ url('/') }}" class="ui:font-display ui:text-[28px] ui:font-black ui:tracking-tight ui:text-white! ui:no-underline! ui:font-stretch-125%">CYWISE</a>
                <div>
                    <x-site.display size="xl" class="ui:text-[clamp(32px,3.6vw,72px)]!">{!! __('Your company<br>is already<br><span class="ui:text-brand-500">a target.</span>') !!}</x-site.display>
                    <p class="ui:m-0 ui:mt-6 ui:max-w-[460px] ui:font-display ui:text-lg ui:leading-normal ui:text-slate-400">{{ __('Cywise finds your exposed assets, vulnerabilities and leaked credentials before attackers do.') }}</p>
                </div>
                <p class="ui:m-0 ui:font-display ui:text-lg ui:font-extrabold ui:uppercase ui:text-brand-500 ui:font-stretch-112%">{{ __('Cybersecurity for everyone.') }}</p>
            </div>
        </aside>

        {{-- Form --}}
        <main id="auth-main-content" class="ui:flex ui:min-w-0 ui:flex-col">
            <x-site.strip class="ui:lg:hidden"/>
            <a href="{{ url('/') }}" class="ui:mx-6 ui:mt-6 ui:self-start ui:font-display ui:text-[22px] ui:font-black ui:tracking-tight ui:text-ink! ui:no-underline! ui:font-stretch-125% ui:lg:hidden">CYWISE</a>
            <div class="ui:flex ui:flex-1 ui:items-center ui:justify-center ui:px-6 ui:py-12">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
