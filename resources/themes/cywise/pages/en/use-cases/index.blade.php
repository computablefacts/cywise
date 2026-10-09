<?php

use function Laravel\Folio\name;

name('website.en.use-cases.index');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.use-cases.index')"
    :seo="[
        'title' => 'Use Cases — Cywise',
        'description' => 'Start with the problem. Cywise maps it to a clear security workflow.',
    ]"
>
@include('theme::partials.website-v2.use-cases-index', ['locale' => 'en'])
</x-layouts.website-v2>
