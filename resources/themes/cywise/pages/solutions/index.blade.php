<?php

use function Laravel\Folio\name;

name('website.solutions.index');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.solutions.index')"
    :seo="[
        'title' => 'Nos Solutions — Cywise',
        'description' => 'Découvrez les capacités de sécurité adaptées à vos besoins.',
    ]"
>
<main>
    {{-- Hero --}}
    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-12 ui:pt-16 ui:sm:px-6">
        <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">Une plateforme.<br><span class="ui:text-slate-400">Une défense complète.</span></x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Activez les capacités de sécurité adaptées à vos priorités du moment.</p>
    </section>

    {{-- Solutions: each keeps its tone everywhere on the site (see site/edge-card) --}}
    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:sm:px-6">
        <div class="ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-2 ui:xl:grid-cols-3">
            @foreach([
                ['critical', 'Surface d’attaque', 'Visualisez vos domaines, services et actifs exposés.', 'website.solutions.attack-surface'],
                ['medium', 'Gestion des vulnérabilités', 'Détectez et priorisez vos vulnérabilités critiques.', 'website.solutions.vulnerability-management'],
                ['low', 'Surveillance des identifiants', 'Identifiez vos données compromises sur le web.', 'website.solutions.credential-monitoring'],
                ['brand', 'CyberBuddy', 'Interrogez votre assistant IA sur la sécurité de votre entreprise.', 'website.solutions.cyberbuddy'],
                ['ink', 'Pentest', 'Faites tester vos systèmes par nos experts en sécurité offensive.', 'website.solutions.pentest'],
            ] as [$tone, $title, $text, $route])
                <x-site.edge-card :tone="$tone" :href="route($route)">
                    <span class="ui:text-2xl ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%">{{ $title }}</span>
                    <span class="ui:flex-1 ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</span>
                    <span class="ui:mt-2 ui:text-sm ui:font-bold ui:uppercase ui:group-hover:text-brand-700">Découvrir →</span>
                </x-site.edge-card>
            @endforeach
        </div>
    </section>
</main>
</x-layouts.website-v2>
