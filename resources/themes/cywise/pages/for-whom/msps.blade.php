<?php

use function Laravel\Folio\name;

name('website.audiences.msps');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.audiences.msps')"
    :seo="[
        'title' => 'MSP — Cywise',
        'description' => 'Standardisez la visibilité et les conseils sécurité sur les environnements clients.',
    ]"
>
@include('theme::partials.website-v2.audience', ['locale' => 'fr', 'audience' => 'msps'])
</x-layouts.website-v2>
