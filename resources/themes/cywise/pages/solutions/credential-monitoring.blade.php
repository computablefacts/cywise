<?php

use function Laravel\Folio\name;

name('website.solutions.credential-monitoring');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.solutions.credential-monitoring')"
    :seo="[
        'title' => 'Surveillance des identifiants — Cywise',
        'description' => 'Détectez vos identifiants compromis sur le web et protégez vos accès critiques.',
    ]"
>
<main>
    {{-- Hero: the accent is the solution's tone (credentials → low, marker for contrast) --}}
    <section class="ui:mx-auto ui:grid ui:max-w-[1240px] ui:grid-cols-1 ui:items-end ui:gap-12 ui:px-4 ui:pb-22 ui:pt-16 ui:sm:px-6 ui:lg:grid-cols-2">
        <div>
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">Découvrez vos données <span class="ui:bg-low ui:px-[0.06em] ui:box-decoration-clone">exposées.</span></x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Détectez vos identifiants compromis sur le web avant qu'ils ne soient exploités par des cyberattaquants.</p>
            <div class="ui:mt-8 ui:flex ui:flex-wrap ui:gap-3">
                <x-site.button :href="route('register')">Surveiller mes identifiants →</x-site.button>
                <x-site.button variant="outline" :href="route('website.solutions.index')">Découvrir Cywise</x-site.button>
            </div>
        </div>

        {{-- Leaked accounts, as in the app (example data) --}}
        <ul class="ui:m-0 ui:list-none ui:overflow-hidden ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-0 ui:font-sans ui:shadow-xs" aria-hidden="true">
            @foreach([['critical', 'j.martin@acme.fr', 'Mot de passe exposé'], ['medium', 'compta@acme.fr', 'À vérifier'], ['low', 's.durand@acme.fr', 'Réinitialisé'], ['low', 'rh@acme.fr', 'Réinitialisé']] as [$level, $account, $state])
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
            <x-site.display>Protégez vos accès collaborateurs.</x-site.display>
            <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-3 ui:sm:grid-cols-2">
                @foreach([
                    ['Détection des fuites', 'Identifiez vos identifiants exposés lors de compromissions de sites tiers.'],
                    ['Surveillance de domaines', 'Scannez en continu les comptes liés à vos domaines d’entreprise.'],
                    ['Plans d’action', 'Recevez des instructions précises pour sécuriser les comptes compromis en priorité.'],
                    ['Alertes temps réel', 'Soyez notifié immédiatement dès qu’une nouvelle fuite impacte votre organisation.'],
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
            ['title' => 'Ciblez vos domaines.', 'text' => 'Indiquez les domaines et les comptes clés que Cywise doit surveiller.'],
            ['title' => 'Identifiez les fuites.', 'text' => 'Nous parcourons les bases de données de compromissions pour trouver vos accès exposés.'],
            ['title' => 'Sécurisez vos accès.', 'text' => 'Appliquez nos recommandations pour réinitialiser les comptes à risque et renforcer votre sécurité.'],
        ]"
    >
        <x-slot:title>Surveillez.<br>Détectez.<br><span class="ui:text-brand-500">Sécurisez.</span></x-slot:title>
    </x-site.steps>

    <x-site.cta :href="route('register')" label="Surveiller mes identifiants →">
        <x-slot:title>Ne laissez plus vos identifiants<br>compromis sans protection.</x-slot:title>
    </x-site.cta>
</main>
</x-layouts.website-v2>
