<?php

use function Laravel\Folio\name;

name('website.audiences.it-teams');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.audiences.it-teams')"
    :seo="[
        'title' => 'Équipes IT — Cywise',
        'description' => 'Donnez aux équipes IT une liste d’actions de sécurité claire.',
    ]"
>
@include('theme::partials.website-v2.audience', ['locale' => 'fr', 'audience' => 'it-teams'])
</x-layouts.website-v2>
