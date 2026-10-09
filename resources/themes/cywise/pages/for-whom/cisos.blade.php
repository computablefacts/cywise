<?php

use function Laravel\Folio\name;

name('website.audiences.cisos');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.audiences.cisos')"
    :seo="[
        'title' => 'RSSI — Cywise',
        'description' => 'Donnez aux responsables sécurité une vue claire de l’exposition, des priorités et des progrès.',
    ]"
>
@include('theme::partials.website-v2.audience', ['locale' => 'fr', 'audience' => 'cisos'])
</x-layouts.website-v2>
