<?php

use function Laravel\Folio\name;

name('website.use-cases.prepare-audit');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.prepare-audit')"
    :seo="[
        'title' => 'Préparer un audit — Cywise',
        'description' => 'Organisez les constats, politiques et progrès avant un audit ou une revue client.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'fr', 'case' => 'prepare-audit'])
</x-layouts.website-v2>
