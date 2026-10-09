<?php

use function Laravel\Folio\name;

name('website.use-cases.check-leaked-credentials');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.check-leaked-credentials')"
    :seo="[
        'title' => 'Vérifier les identifiants compromis — Cywise',
        'description' => 'Détectez les identifiants exposés et réduisez le risque de prise de contrôle de compte.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'fr', 'case' => 'check-leaked-credentials'])
</x-layouts.website-v2>
