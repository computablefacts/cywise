<?php

use function Laravel\Folio\name;

name('website.en.company');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.company')"
    :seo="[
        'title' => 'Company — Cywise',
        'description' => 'Cywise, a French cybersecurity SaaS platform: our vision, story, values and team.',
    ]"
>
@include('theme::partials.website-v2.company', ['locale' => 'en'])
</x-layouts.website-v2>
