<?php

use function Laravel\Folio\name;

name('website.en.solutions.index');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.solutions.index')"
    :seo="[
        'title' => 'Solutions — Cywise',
        'description' => 'Choose the security capability that matches your current need.',
    ]"
>
<main>
    {{-- Hero --}}
    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-12 ui:pt-16 ui:sm:px-6">
        <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">One platform.<br><span class="ui:text-slate-400">Multiple defenses.</span></x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Choose the security capability that matches your current need.</p>
    </section>

    {{-- Solutions: each keeps its tone everywhere on the site (see site/edge-card) --}}
    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:sm:px-6">
        <div class="ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-2 ui:xl:grid-cols-3">
            @foreach([
                ['critical', 'Attack surface', 'See domains, services and public assets.', 'website.en.solutions.attack-surface'],
                ['medium', 'Vulnerability management', 'Find and prioritize weaknesses.', 'website.en.solutions.vulnerability-management'],
                ['low', 'Credential monitoring', 'Detect compromised company credentials.', 'website.en.solutions.credential-monitoring'],
                ['brand', 'CyberBuddy', 'Ask security questions from company context.', 'website.en.solutions.cyberbuddy'],
                ['info', 'PSSI', 'Build a usable security policy.', 'website.en.solutions.pssi'],
                ['ink', 'Pentest', 'Test critical systems with human experts.', 'website.en.solutions.pentest'],
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
