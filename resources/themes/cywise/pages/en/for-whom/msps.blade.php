<?php

use function Laravel\Folio\name;

name('website.en.audiences.msps');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.audiences.msps')"
    :seo="[
        'title' => 'MSPs — Cywise',
        'description' => 'Standardize visibility and security guidance across customer environments.',
    ]"
>
@include('theme::partials.website-v2.audience', ['locale' => 'en', 'audience' => 'msps'])
</x-layouts.website-v2>
