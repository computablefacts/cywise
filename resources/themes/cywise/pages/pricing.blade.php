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
<main><section class="page-hero page-hero-acid"><div class="container-fluid shell"><span class="mono">TARIFS</span><h1>LA SÉCURITÉ SANS LA COMPLEXITÉ DES GRANDS GROUPES.</h1><p>Commencez par une visibilité claire. Ajoutez des tests experts lorsque nécessaire.</p></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="row g-4 justify-content-center">
@include('theme::partials.website-v2.plans', ['locale' => 'fr'])
</div></div></section></main>
</x-layouts.website-v2>
