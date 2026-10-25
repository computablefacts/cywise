<?php

use function Laravel\Folio\name;

name('website.audiences.index');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.audiences.index')"
    :seo="[
        'title' => 'Pour qui — Cywise',
        'description' => 'Découvrez l’expérience Cywise adaptée à votre organisation et à vos enjeux de sécurité.',
    ]"
>
@include('theme::partials.website-v2.audiences', ['locale' => 'fr'])
</x-layouts.website-v2>
