<?php

use function Laravel\Folio\name;

name('website.en.audiences.startups');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.audiences.startups')"
    :seo="[
        'title' => 'Startups — Cywise',
        'description' => 'Build security foundations while your product and team grow.',
    ]"
>
@include('theme::partials.website-v2.audience', ['locale' => 'en', 'audience' => 'startups'])
</x-layouts.website-v2>
