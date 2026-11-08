<?php

use function Laravel\Folio\{name};

name('pricing');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.pricing')"
    :seo="[
        'title' => 'Tarifs — Cywise',
        'description' => 'Des offres Cywise simples et adaptées aux équipes en croissance.',
    ]"
>
<main>
    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-14 ui:pt-16 ui:sm:px-6">
        <x-site.display as="h1" size="xl">La sécurité sans la complexité des grands groupes.</x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[560px] ui:text-xl ui:leading-normal ui:text-slate-700">Commencez par une visibilité claire. Ajoutez des tests experts lorsque nécessaire.</p>
    </section>

    <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:sm:px-6">
        <div class="ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-2 ui:xl:grid-cols-4">
            @include('theme::partials.website-v2.plans', ['locale' => 'fr'])
        </div>

        <div class="ui:mt-16 ui:rounded-2xl ui:bg-slate-50 ui:p-8 ui:lg:p-12">
            <x-site.display size="sm" class="ui:mb-8">Comprendre nos modules.</x-site.display>
            <div class="ui:grid ui:grid-cols-1 ui:gap-8 ui:md:grid-cols-2">
                <div>
                    <h3 class="ui:m-0 ui:text-lg ui:font-bold">CyberTodo & CyberScribe</h3>
                    <p class="ui:m-0 ui:mt-2 ui:text-slate-600">Vos outils de pilotage : gérez votre plan d'action (CyberTodo) et générez vos documents de sécurité (CyberScribe) en quelques clics.</p>
                </div>
                <div>
                    <h3 class="ui:m-0 ui:text-lg ui:font-bold">Agents & Hardening</h3>
                    <p class="ui:m-0 ui:mt-2 ui:text-slate-600">Nos agents surveillent vos systèmes en continu et vous guident dans le durcissement (Hardening) de vos configurations.</p>
                </div>
            </div>
        </div>
    </section>
</main>
</x-layouts.website-v2>
