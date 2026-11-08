<?php

use function Laravel\Folio\name;

name('website.solutions.index');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.solutions.index')"
    :seo="[
        'title' => 'Nos Solutions — Cywise',
        'description' => 'Découvrez les capacités de sécurité de Cywise pour protéger votre entreprise.',
    ]"
>
<main>
    {{-- Hero --}}
    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-12 ui:pt-16 ui:sm:px-6">
        <x-site.display as="h1" size="xl">Une plateforme unique<br><span class="ui:text-brand-500">au service de votre cyberdéfense.</span></x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">Activez les capacités de sécurité adaptées à vos enjeux et protégez vos actifs critiques.</p>
    </section>

    {{-- Solutions --}}
    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:sm:px-6">
        <div class="ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-2 ui:xl:grid-cols-3">
            @foreach([
                ['critical', 'Surface d’attaque', 'Maîtrisez votre exposition externe et visualisez vos actifs comme un attaquant.', 'website.solutions.attack-surface'],
                ['medium', 'Gestion des vulnérabilités', 'Détectez, priorisez et corrigez vos failles de sécurité avant qu’elles ne soient exploitées.', 'website.solutions.vulnerability-management'],
                ['low', 'Identifiants compromis', 'Surveillez le web pour identifier vos données collaborateurs exposées.', 'website.solutions.credential-monitoring'],
                ['info', 'CyberBuddy', 'Interrogez votre assistant IA pour obtenir des conseils clairs et des plans d’action.', 'website.solutions.cyberbuddy'],
                ['ink', 'Pentest expert', 'Faites évaluer vos systèmes critiques par nos experts en sécurité offensive.', 'website.solutions.pentest'],
            ] as [$tone, $title, $text, $route])
                <x-site.edge-card :tone="$tone" :href="route($route)">
                    <span class="ui:text-2xl ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%">{{ $title }}</span>
                    <span class="ui:flex-1 ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</span>
                    <span class="ui:mt-2 ui:text-sm ui:font-bold ui:uppercase ui:group-hover:text-brand-700">Découvrir →</span>
                </x-site.edge-card>
            @endforeach
        </div>
    </section>

    {{-- Testimonials / Use Cases --}}
    <section class="ui:border-0 ui:border-t ui:border-solid ui:border-line ui:bg-canvas">
        <div class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:py-24 ui:sm:px-6">
            <x-site.display class="ui:max-w-[800px]">Répondez à vos enjeux concrets.</x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[500px] ui:text-lg ui:leading-normal ui:text-slate-600">Nos clients utilisent Cywise pour sécuriser leur quotidien, préparer leurs audits et renforcer leur posture de sécurité.</p>

            <div class="ui:mt-12 ui:grid ui:gap-8 ui:lg:grid-cols-3">
                @foreach([
                    ['Sylvain M.', 'RSSI d’Oppscience', 'La solution a amélioré notre visibilité des périmètres exposés. Nous sommes alertés dès qu’un changement important est détecté : l’idéal pour une PME.'],
                    ['Augustin B.', 'Co-fondateur d’Ackero', 'Nous avons produit une PSSI claire et complète pour notre client en quelques jours au lieu de plusieurs semaines. Un vrai gain de temps et de qualité.'],
                    ['Julien B.', 'CISO d’un groupe du CAC40', 'Cywise est le meilleur produit que j’ai vu en matière de détection de vulnérabilités de qualité, sans faux positifs.'],
                ] as [$author, $role, $quote])
                    <div class="ui:flex ui:flex-col">
                        <div class="ui:flex-1 ui:text-lg ui:italic ui:leading-relaxed ui:text-slate-700">"{{ $quote }}"</div>
                        <div class="ui:mt-6">
                            <div class="ui:font-bold ui:text-ink">{{ $author }}</div>
                            <div class="ui:text-sm ui:text-slate-500">{{ $role }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</main>
</x-layouts.website-v2>
