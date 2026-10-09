<?php

use function Laravel\Folio\name;

name('website.use-cases.index');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.index')"
    :seo="[
        'title' => 'Cas d’usage — Cywise',
        'description' => 'Partez du problème. Cywise le transforme en flux de sécurité clair.',
    ]"
>
@include('theme::partials.website-v2.use-cases-index', ['locale' => 'fr'])
</x-layouts.website-v2>
