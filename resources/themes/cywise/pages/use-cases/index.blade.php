<?php

use function Laravel\Folio\name;

name('website.use-cases.index');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.index')"
    :seo="[
        'title' => 'Cas d’usage — Cywise',
        'description' => 'Découvrez comment Cywise répond à vos enjeux concrets de cybersécurité.',
    ]"
>
@include('theme::partials.website-v2.use-cases-index', ['locale' => 'fr'])
</x-layouts.website-v2>
