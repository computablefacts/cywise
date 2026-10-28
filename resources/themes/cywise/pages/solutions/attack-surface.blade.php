<?php

use function Laravel\Folio\name;

name('website.solutions.attack-surface');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.solutions.attack-surface')"
    :seo="[
        'title' => 'Surface d’attaque — Cywise',
        'description' => 'Cartographiez et surveillez votre exposition publique pour devancer les cyberattaques.',
    ]"
>
<main>
    {{-- Hero: the accent is the solution's tone (attack surface → critical, see site/edge-card) --}}
    <section class="ui:mx-auto ui:grid ui:max-w-[1240px] ui:grid-cols-1 ui:items-end ui:gap-12 ui:px-4 ui:pb-22 ui:pt-16 ui:sm:px-6 ui:lg:grid-cols-2">
        <div>
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">Visualisez votre entreprise du point de vue des <span class="ui:text-critical">attaquants.</span></x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Découvrez et surveillez vos actifs exposés pour éliminer les risques avant qu'ils ne soient exploités.</p>
            <div class="ui:mt-8 ui:flex ui:flex-wrap ui:gap-3">
                <x-site.button :href="route('register')">Surveiller ma surface d'attaque →</x-site.button>
                <x-site.button variant="outline" :href="route('website.solutions.index')">Découvrir Cywise</x-site.button>
            </div>
        </div>

        {{-- Inventory rows, as in the app (example data) --}}
        <ul class="ui:m-0 ui:list-none ui:overflow-hidden ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-0 ui:font-sans ui:shadow-xs" aria-hidden="true">
            @foreach([['critical', 'admin.acme.fr', '443', 'Exposé'], ['medium', 'vpn.acme.fr', '22', 'À vérifier'], ['medium', 'staging.acme.fr', '8080', 'À vérifier'], ['low', 'www.acme.fr', '443', 'OK'], ['low', 'mail.acme.fr', '25', 'OK']] as [$level, $host, $port, $state])
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
            <x-site.display>Maîtrisez votre exposition externe.</x-site.display>
            <div class="ui:mt-12 ui:grid ui:gap-3 ui:sm:grid-cols-2">
                @foreach([
                    ['Inventaire des actifs', 'Identifiez automatiquement vos domaines, sous-domaines et adresses IP.'],
                    ['Analyse des ports', 'Détectez les services exposés et les ports ouverts par erreur sur Internet.'],
                    ['Surveillance 24/7', 'Recevez une alerte dès qu’un nouvel actif ou service apparaît dans votre périmètre.'],
                    ['Priorisation IT', 'Fournissez à vos équipes une liste précise d’actions pour réduire votre surface d’attaque.'],
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
            ['title' => 'Cartographiez vos actifs.', 'text' => 'Cywise identifie automatiquement tout ce que votre entreprise expose sur le web.'],
            ['title' => 'Évaluez les risques.', 'text' => 'Nous mettons en lumière les services critiques qui nécessitent une attention immédiate.'],
            ['title' => 'Réduisez votre exposition.', 'text' => 'Fermez les accès inutiles et sécurisez vos services grâce à nos guides pas à pas.'],
        ]"
    >
        <x-slot:title>Découvrez.<br>Analysez.<br><span class="ui:text-brand-500">Sécurisez.</span></x-slot:title>
    </x-site.steps>

    <x-site.cta :href="route('register')" label="Surveiller ma surface d'attaque →">
        <x-slot:title>Prenez le contrôle de<br>votre périmètre exposé.</x-slot:title>
    </x-site.cta>
</main>
</x-layouts.website-v2>
