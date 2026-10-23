<?php

use function Laravel\Folio\name;

name('website.company');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.company')"
    :seo="[
        'title' => 'La société — Cywise',
        'description' => 'Cywise, plateforme SaaS française de cybersécurité : notre vision, notre histoire, nos valeurs et notre équipe.',
    ]"
>
@include('theme::partials.website-v2.company', ['locale' => 'fr'])
</x-layouts.website-v2>
