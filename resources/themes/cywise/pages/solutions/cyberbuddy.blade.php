<?php

use function Laravel\Folio\name;

name('website.solutions.cyberbuddy');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.solutions.cyberbuddy')"
    :seo="[
        'title' => 'CyberBuddy — Cywise',
        'description' => 'Utilisez un assistant sécurité qui répond selon le contexte de votre entreprise.',
    ]"
>
<main>
    {{-- Hero: the accent is the solution's tone (CyberBuddy → brand) --}}
    <section class="ui:mx-auto ui:grid ui:max-w-[1240px] ui:grid-cols-1 ui:items-end ui:gap-12 ui:px-4 ui:pb-22 ui:pt-16 ui:sm:px-6 ui:lg:grid-cols-2">
        <div>
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">Demandez. Comprenez. <span class="ui:text-brand-500">Agissez.</span></x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Utilisez un assistant sécurité qui répond selon le contexte de votre entreprise.</p>
            <div class="ui:mt-8 ui:flex ui:flex-wrap ui:gap-3">
                <x-site.button :href="route('register')">Commencer →</x-site.button>
                <x-site.button variant="outline" :href="route('website.solutions.index')">Découvrir Cywise</x-site.button>
            </div>
        </div>

        {{-- The app's chat window (Inter, same tokens as pages/cyberbuddy, example data) --}}
        <div class="ui:min-w-0 ui:overflow-hidden ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-white ui:font-sans ui:text-ink ui:shadow-xs" aria-hidden="true">
            <div class="ui:flex ui:items-center ui:gap-3 ui:border-0 ui:border-b ui:border-solid ui:border-line ui:px-5 ui:py-4">
                <span class="ui:flex ui:size-10 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-xl ui:bg-brand-500 ui:text-white"><x-phosphor-robot-bold class="ui:size-5"/></span>
                <span class="ui:flex ui:flex-col">
                    <span class="ui:text-lg ui:font-semibold ui:leading-6">CyberBuddy</span>
                    <span class="ui:text-sm ui:text-slate-500">Votre assistant IA en cybersécurité</span>
                </span>
            </div>
            <div class="ui:flex ui:flex-col ui:gap-5 ui:p-5">
                <div class="ui:max-w-[75%] ui:self-end ui:rounded-2xl ui:rounded-br-sm ui:bg-ink ui:px-4 ui:py-3 ui:text-sm ui:text-white">Certains de nos serveurs publics sont-ils vulnérables ?</div>
                <div class="ui:flex ui:items-start ui:gap-2.5">
                    <span class="ui:flex ui:rounded-full ui:bg-brand-50 ui:p-2 ui:text-brand-500"><x-phosphor-robot-bold class="ui:size-4"/></span>
                    <div class="ui:min-w-0 ui:max-w-[85%] ui:rounded-2xl ui:rounded-bl-sm ui:border ui:border-solid ui:border-line ui:px-4 ui:py-3 ui:text-sm ui:leading-relaxed">
                        Oui. Trois éléments demandent une action.
                        <ul class="ui:m-0 ui:mt-3 ui:list-none ui:overflow-hidden ui:rounded-lg ui:border ui:border-solid ui:border-line ui:p-0">
                            @foreach([['critical', 'Critique', 'Portail d’administration exposé'], ['high', 'Élevé', 'Composant web obsolète'], ['medium', 'Moyen', 'Configuration TLS']] as [$level, $label, $title])
                                <li data-severity="{{ $level }}" class="ui:flex ui:items-center ui:gap-2.5 ui:px-3 ui:py-2.5 {{ $loop->first ? '' : 'ui:border-0 ui:border-t ui:border-solid ui:border-line' }}">
                                    <x-ui.badge :level="$level">{{ $label }}</x-ui.badge>
                                    {{ $title }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-4">
                <div class="ui:flex ui:items-center ui:gap-2">
                    <span class="ui:block ui:h-10 ui:min-w-0 ui:flex-1 ui:truncate ui:leading-10 ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-slate-50 ui:px-4 ui:text-sm ui:text-slate-400">Posez-moi une question !</span>
                    <x-phosphor-image class="ui:size-5 ui:text-slate-500"/>
                    <span class="ui:flex ui:h-10 ui:items-center ui:gap-2 ui:rounded-lg ui:bg-brand-500 ui:px-4 ui:text-sm ui:font-medium ui:text-white">Envoyer <x-phosphor-paper-plane-right class="ui:size-4"/></span>
                </div>
            </div>
        </div>
    </section>

    {{-- What you get --}}
    <section class="ui:border-0 ui:border-y ui:border-solid ui:border-line ui:bg-canvas">
        <div class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:py-22 ui:sm:px-6">
            <x-site.display>Une sécurité qui reste claire.</x-site.display>
            <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-3 ui:sm:grid-cols-2">
                @foreach([
                    ['Questions directes', 'Posez des questions de sécurité en langage simple.'],
                    ['Contexte entreprise', 'Utilisez vos politiques et données de sécurité pour obtenir des réponses pertinentes.'],
                    ['Accompagnement des politiques', 'Obtenez de l’aide pour la documentation sécurité et la PSSI.'],
                    ['Explication du risque', 'Expliquez les constats techniques aux non-spécialistes.'],
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
