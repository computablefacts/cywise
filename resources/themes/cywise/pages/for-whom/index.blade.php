<?php

use function Laravel\Folio\name;

name('website.audiences.index');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.audiences.index')"
    :seo="[
        'title' => 'Pour qui — Cywise',
        'description' => 'Choisissez l’expérience adaptée à votre organisation ou votre rôle.',
    ]"
>
@include('theme::partials.website-v2.audiences', ['locale' => 'fr'])
</x-layouts.website-v2>
