<?php

use function Laravel\Folio\name;

name('website.audiences.smbs');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.audiences.smbs')"
    :seo="[
        'title' => 'PME — Cywise',
        'description' => 'Donnez aux PME une visibilité claire et des actions concrètes.',
    ]"
>
@include('theme::partials.website-v2.audience', ['locale' => 'fr', 'audience' => 'smbs'])
</x-layouts.website-v2>
