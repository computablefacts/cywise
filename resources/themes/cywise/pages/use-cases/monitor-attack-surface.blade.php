<?php

use function Laravel\Folio\name;

name('website.use-cases.monitor-attack-surface');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.monitor-attack-surface')"
    :seo="[
        'title' => 'Surveiller votre surface d’attaque — Cywise',
        'description' => 'Maîtrisez votre empreinte externe et détectez tout nouvel actif exposé en temps réel.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'fr', 'case' => 'monitor-attack-surface'])
</x-layouts.website-v2>
