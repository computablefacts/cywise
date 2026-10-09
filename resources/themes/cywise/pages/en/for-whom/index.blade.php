<?php

use function Laravel\Folio\name;

name('website.en.audiences.index');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.audiences.index')"
    :seo="[
        'title' => 'For Whom — Cywise',
        'description' => 'Choose the experience that matches your organization or role.',
    ]"
>
@include('theme::partials.website-v2.audiences', ['locale' => 'en'])
</x-layouts.website-v2>
