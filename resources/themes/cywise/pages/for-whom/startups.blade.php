<?php

use function Laravel\Folio\name;

name('website.audiences.startups');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.audiences.startups')"
    :seo="[
        'title' => 'Startups — Cywise',
        'description' => 'Construisez vos bases de sécurité pendant la croissance du produit et de l’équipe.',
    ]"
>
@include('theme::partials.website-v2.audience', ['locale' => 'fr', 'audience' => 'startups'])
</x-layouts.website-v2>
