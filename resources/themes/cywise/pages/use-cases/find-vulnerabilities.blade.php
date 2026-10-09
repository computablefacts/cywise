<?php

use function Laravel\Folio\name;

name('website.use-cases.find-vulnerabilities');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.find-vulnerabilities')"
    :seo="[
        'title' => 'Trouver des vulnérabilités — Cywise',
        'description' => 'Détectez les faiblesses et transformez-les en liste de correction priorisée.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'fr', 'case' => 'find-vulnerabilities'])
</x-layouts.website-v2>
