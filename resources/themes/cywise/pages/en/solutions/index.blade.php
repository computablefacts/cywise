<?php

use function Laravel\Folio\name;

name('website.en.solutions.index');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.solutions.index')"
    :seo="[
        'title' => 'Our Solutions — Cywise',
        'description' => 'Discover the security capabilities adapted to your needs.',
    ]"
>
<main>
    {{-- Hero --}}
    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-12 ui:pt-16 ui:sm:px-6">
        <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">One platform.<br><span class="ui:text-slate-400">Complete defense.</span></x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Activate the security capabilities adapted to your current priorities.</p>
    </section>

    {{-- Solutions: each keeps its tone everywhere on the site (see site/edge-card) --}}
    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:sm:px-6">
        <div class="ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-2 ui:xl:grid-cols-3">
            @foreach([
                ['critical', 'Attack surface', 'Visualize your exposed domains, services and assets.', 'website.en.solutions.attack-surface'],
                ['medium', 'Vulnerability management', 'Detect and prioritize your critical vulnerabilities.', 'website.en.solutions.vulnerability-management'],
                ['low', 'Credential monitoring', 'Identify your compromised data on the web.', 'website.en.solutions.credential-monitoring'],
                ['brand', 'CyberBuddy', 'Ask your AI assistant about your company’s security.', 'website.en.solutions.cyberbuddy'],
                ['ink', 'Pentest', 'Have your systems tested by our offensive security experts.', 'website.en.solutions.pentest'],
            ] as [$tone, $title, $text, $route])
                <x-site.edge-card :tone="$tone" :href="route($route)">
                    <span class="ui:text-2xl ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%">{{ $title }}</span>
                    <span class="ui:flex-1 ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</span>
                    <span class="ui:mt-2 ui:text-sm ui:font-bold ui:uppercase ui:group-hover:text-brand-700">Explore →</span>
                </x-site.edge-card>
            @endforeach
        </div>
    </section>
</main>
</x-layouts.website-v2>
