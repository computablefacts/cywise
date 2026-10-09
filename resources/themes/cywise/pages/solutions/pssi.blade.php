<?php

use function Laravel\Folio\name;

name('website.solutions.pssi');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.solutions.pssi')"
    :seo="[
        'title' => 'PSSI — Cywise',
        'description' => 'Créez une politique de sécurité que les équipes comprennent et utilisent.',
    ]"
>
<main>
    {{-- Hero: the accent is the solution's tone (PSSI → info) --}}
    <section class="ui:mx-auto ui:grid ui:max-w-[1240px] ui:grid-cols-1 ui:items-end ui:gap-12 ui:px-4 ui:pb-22 ui:pt-16 ui:sm:px-6 ui:lg:grid-cols-2">
        <div>
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">Créez votre politique de <span class="ui:text-info">sécurité.</span></x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Créez une politique de sécurité que les équipes comprennent et utilisent.</p>
            <div class="ui:mt-8 ui:flex ui:flex-wrap ui:gap-3">
                <x-site.button :href="route('register')">Commencer →</x-site.button>
                <x-site.button variant="outline" :href="route('website.solutions.index')">Découvrir Cywise</x-site.button>
            </div>
        </div>

        {{-- Policy chapters with owner and review state (example data) --}}
        <div class="ui:overflow-hidden ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:font-sans ui:shadow-xs" aria-hidden="true">
            <div class="ui:px-5 ui:py-3.5 ui:font-mono ui:text-xs ui:uppercase ui:tracking-wider ui:text-slate-500">PSSI — Acme</div>
            <ul class="ui:m-0 ui:list-none ui:p-0">
                @foreach([['low', 'Gestion des accès', 'RSSI', 'Validé'], ['low', 'Sauvegardes', 'IT', 'Validé'], ['medium', 'Postes de travail', 'IT', 'En revue'], ['info', 'Gestion des incidents', 'DG', 'Brouillon']] as [$level, $chapter, $owner, $state])
                    <li data-severity="{{ $level }}" class="ui:flex ui:items-center ui:gap-3 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-3.5">
                        <span class="ui:min-w-0 ui:flex-1 ui:text-[15px] ui:font-medium">{{ $chapter }}</span>
                        <x-ui.tag>{{ $owner }}</x-ui.tag>
                        <x-ui.badge :level="$level">{{ $state }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- What you get --}}
    <section class="ui:border-0 ui:border-y ui:border-solid ui:border-line ui:bg-canvas">
        <div class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:py-22 ui:sm:px-6">
            <x-site.display>Une sécurité qui reste claire.</x-site.display>
            <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-3 ui:sm:grid-cols-2">
                @foreach([
                    ['Politique structurée', 'Construisez une PSSI claire à partir de contrôles pratiques.'],
                    ['Responsabilité', 'Attribuez des responsabilités claires.'],
                    ['Cycle de revue', 'Gardez les politiques à jour lorsque l’entreprise évolue.'],
                    ['Assistance CyberBuddy', 'Posez des questions sur chaque domaine de politique.'],
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
            ['title' => 'Définissez votre périmètre.', 'text' => 'Définissez ce que Cywise doit surveiller pour votre organisation.'],
            ['title' => 'Trouvez les risques importants.', 'text' => 'Cywise regroupe les signaux et met en évidence les constats qui nécessitent une action.'],
            ['title' => 'Corrigez avec des conseils clairs.', 'text' => 'Votre équipe reçoit des actions concrètes, sans complexité inutile.'],
        ]"
    >
        <x-slot:title>Voyez.<br>Priorisez.<br><span class="ui:text-brand-500">Agissez.</span></x-slot:title>
    </x-site.steps>

    <x-site.cta :href="route('register')" label="Commencer →">
        <x-slot:title>Rendez votre<br>sécurité visible.</x-slot:title>
    </x-site.cta>
</main>
</x-layouts.website-v2>
