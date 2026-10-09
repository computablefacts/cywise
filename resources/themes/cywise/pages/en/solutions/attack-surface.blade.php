<?php

use function Laravel\Folio\name;

name('website.en.solutions.attack-surface');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.solutions.attack-surface')"
    :seo="[
        'title' => 'Attack Surface — Cywise',
        'description' => 'Map your public exposure before attackers use it.',
    ]"
>
<main>
    {{-- Hero: the accent is the solution's tone (attack surface → critical, see site/edge-card) --}}
    <section class="ui:mx-auto ui:grid ui:max-w-[1240px] ui:grid-cols-1 ui:items-end ui:gap-12 ui:px-4 ui:pb-22 ui:pt-16 ui:sm:px-6 ui:lg:grid-cols-2">
        <div>
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">See what attackers <span class="ui:text-critical">see.</span></x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Map your public exposure before attackers use it.</p>
            <div class="ui:mt-8 ui:flex ui:flex-wrap ui:gap-3">
                <x-site.button :href="route('register')">Start protecting →</x-site.button>
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
            <x-site.display>Security that stays clear.</x-site.display>
            <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-3 ui:sm:grid-cols-2">
                @foreach([
                    ['Asset discovery', 'Find public domains, hosts and services connected to your company.'],
                    ['Port visibility', 'See exposed services and unexpected internet-facing ports.'],
                    ['Continuous monitoring', 'Track changes in your external exposure over time.'],
                    ['Action list', 'Give IT teams a short list of concrete fixes.'],
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
            ['title' => 'Connect your scope.', 'text' => 'Define what Cywise must monitor for your organization.'],
            ['title' => 'Find the important risks.', 'text' => 'Cywise groups signals and highlights the findings that need action.'],
            ['title' => 'Fix with clear guidance.', 'text' => 'Your team gets direct actions without unnecessary security complexity.'],
        ]"
    >
        <x-slot:title>See.<br>Prioritize.<br><span class="ui:text-brand-500">Act.</span></x-slot:title>
    </x-site.steps>

    <x-site.cta :href="route('register')" label="Start protecting →">
        <x-slot:title>Make your<br>security visible.</x-slot:title>
    </x-site.cta>
</main>
</x-layouts.website-v2>
