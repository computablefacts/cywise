<?php

use function Laravel\Folio\name;

name('website.en.solutions.attack-surface');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.solutions.attack-surface')"
    :seo="[
        'title' => 'Attack Surface — Cywise',
        'description' => 'Map and monitor your public exposure to stay ahead of cyberattacks.',
    ]"
>
<main>
    {{-- Hero: the accent is the solution's tone (attack surface → critical, see site/edge-card) --}}
    <section class="ui:mx-auto ui:grid ui:max-w-[1240px] ui:grid-cols-1 ui:items-end ui:gap-12 ui:px-4 ui:pb-22 ui:pt-16 ui:sm:px-6 ui:lg:grid-cols-2">
        <div>
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">Visualize your company from the <span class="ui:text-critical">attackers’ view.</span></x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Discover and monitor your exposed assets to eliminate risks before they are exploited.</p>
            <div class="ui:mt-8 ui:flex ui:flex-wrap ui:gap-3">
                <x-site.button :href="route('register')">Monitor my attack surface →</x-site.button>
                <x-site.button variant="outline" :href="route('website.en.solutions.index')">Explore Cywise</x-site.button>
            </div>
        </div>

        {{-- Inventory rows, as in the app (example data) --}}
        <ul class="ui:m-0 ui:list-none ui:overflow-hidden ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-0 ui:font-sans ui:shadow-xs" aria-hidden="true">
            @foreach([['critical', 'admin.acme.fr', '443', 'Exposed'], ['medium', 'vpn.acme.fr', '22', 'To check'], ['medium', 'staging.acme.fr', '8080', 'To check'], ['low', 'www.acme.fr', '443', 'OK'], ['low', 'mail.acme.fr', '25', 'OK']] as [$level, $host, $port, $state])
                <li data-severity="{{ $level }}" class="ui:flex ui:items-center ui:gap-3 ui:px-5 ui:py-3.5 {{ $loop->first ? '' : 'ui:border-0 ui:border-t ui:border-solid ui:border-line' }}">
                    <span class="ui:flex-1 ui:font-mono ui:text-sm">{{ $host }}</span>
                    <x-ui.tag>{{ $port }}</x-ui.tag>
                    <x-ui.badge :level="$level">{{ $state }}</x-ui.badge>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- What you get --}}
    <section class="ui:border-0 ui:border-y ui:border-solid ui:border-line ui:bg-canvas">
        <div class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:py-22 ui:sm:px-6">
            <x-site.display>Master your external exposure.</x-site.display>
            <div class="ui:mt-12 ui:grid ui:gap-3 ui:sm:grid-cols-2">
                @foreach([
                    ['Asset inventory', 'Automatically identify your domains, subdomains and IP addresses.'],
                    ['Port analysis', 'Detect exposed services and ports opened by mistake on the Internet.'],
                    ['24/7 Monitoring', 'Get an alert as soon as a new asset or service appears in your scope.'],
                    ['IT prioritization', 'Provide your teams with a precise list of actions to reduce your attack surface.'],
                ] as [$title, $text])
                    <article class="ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-6">
                        <h3 class="ui:m-0 ui:text-2xl ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%">{{ $title }}</h3>
                        <p class="ui:m-0 ui:mt-3 ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <x-site.steps
        :steps="[
            ['title' => 'Map your assets.', 'text' => 'Cywise automatically identifies everything your company exposes on the web.'],
            ['title' => 'Evaluate risks.', 'text' => 'We highlight the critical services that require immediate attention.'],
            ['title' => 'Reduce exposure.', 'text' => 'Close unnecessary access and secure your services with our step-by-step guides.'],
        ]"
    >
        <x-slot:title>Discover.<br>Analyze.<br><span class="ui:text-brand-500">Secure.</span></x-slot:title>
    </x-site.steps>

    <x-site.cta :href="route('register')" label="Monitor my attack surface →">
        <x-slot:title>Take control of<br>your exposed perimeter.</x-slot:title>
    </x-site.cta>
</main>
</x-layouts.website-v2>
