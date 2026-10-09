<?php

use function Laravel\Folio\name;

name('website.use-cases.create-pssi');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.create-pssi')"
    :seo="[
        'title' => 'Créer une PSSI — Cywise',
        'description' => 'Créez une politique de sécurité structurée adaptée à votre organisation.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'fr', 'case' => 'create-pssi'])
</x-layouts.website-v2>
