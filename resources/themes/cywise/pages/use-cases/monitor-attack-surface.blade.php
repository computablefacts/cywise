<?php

use function Laravel\Folio\name;

name('website.use-cases.monitor-attack-surface');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.monitor-attack-surface')"
    :seo="[
        'title' => 'Surveiller la surface d’attaque — Cywise',
        'description' => 'Suivez les actifs publics et les services exposés lorsqu’ils évoluent.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'fr', 'case' => 'monitor-attack-surface'])
</x-layouts.website-v2>
