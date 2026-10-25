<?php

use function Laravel\Folio\name;

name('website.en.solutions.credential-monitoring');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.solutions.credential-monitoring')"
    :seo="[
        'title' => 'Credential Monitoring — Cywise',
        'description' => 'Detect your compromised credentials on the web and protect your critical access.',
    ]"
>
<main>
    {{-- Hero: the accent is the solution's tone (credentials → low, marker for contrast) --}}
    <section class="ui:mx-auto ui:grid ui:max-w-[1240px] ui:grid-cols-1 ui:items-end ui:gap-12 ui:px-4 ui:pb-22 ui:pt-16 ui:sm:px-6 ui:lg:grid-cols-2">
        <div>
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">Discover your <span class="ui:bg-low ui:px-[0.06em] ui:box-decoration-clone">leaked data.</span></x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Detect your compromised credentials on the web before they are exploited by cyberattackers.</p>
            <div class="ui:mt-8 ui:flex ui:flex-wrap ui:gap-3">
                <x-site.button :href="route('register')">Monitor my credentials →</x-site.button>
                <x-site.button variant="outline" :href="route('website.en.solutions.index')">Explore Cywise</x-site.button>
            </div>
        </div>

        {{-- Leaked accounts, as in the app (example data) --}}
        <ul class="ui:m-0 ui:list-none ui:overflow-hidden ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-0 ui:font-sans ui:shadow-xs" aria-hidden="true">
            @foreach([['critical', 'j.martin@acme.fr', 'Password exposed'], ['medium', 'accounting@acme.fr', 'To check'], ['low', 's.durand@acme.fr', 'Reset'], ['low', 'hr@acme.fr', 'Reset']] as [$level, $account, $state])
                <li data-severity="{{ $level }}" class="ui:flex ui:items-center ui:gap-3 ui:px-5 ui:py-3.5 {{ $loop->first ? '' : 'ui:border-0 ui:border-t ui:border-solid ui:border-line' }}">
                    <span class="ui:min-w-0 ui:flex-1 ui:truncate ui:font-mono ui:text-sm">{{ $account }}</span>
                    <x-ui.badge :level="$level">{{ $state }}</x-ui.badge>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- What you get --}}
    <section class="ui:border-0 ui:border-y ui:border-solid ui:border-line ui:bg-canvas">
        <div class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:py-22 ui:sm:px-6">
            <x-site.display>Protect your employees’ access.</x-site.display>
            <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-3 ui:sm:grid-cols-2">
                @foreach([
                    ['Leak detection', 'Identify your credentials exposed during third-party site breaches.'],
                    ['Domain monitoring', 'Continuously scan accounts linked to your company domains.'],
                    ['Action plans', 'Receive precise instructions to secure compromised accounts as a priority.'],
                    ['Real-time alerts', 'Be notified immediately as soon as a new leak impacts your organization.'],
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
            ['title' => 'Target your domains.', 'text' => 'Indicate the domains and key accounts that Cywise should monitor.'],
            ['title' => 'Identify leaks.', 'text' => 'We scan data breach databases to find your exposed access.'],
            ['title' => 'Secure your access.', 'text' => 'Apply our recommendations to reset high-risk accounts and strengthen your security.'],
        ]"
    >
        <x-slot:title>Monitor.<br>Detect.<br><span class="ui:text-brand-500">Secure.</span></x-slot:title>
    </x-site.steps>

    <x-site.cta :href="route('register')" label="Monitor my credentials →">
        <x-slot:title>Don’t leave your compromised<br>credentials unprotected.</x-slot:title>
    </x-site.cta>
</main>
</x-layouts.website-v2>
