<?php

use function Laravel\Folio\name;

name('website.en.use-cases.monitor-attack-surface');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.use-cases.monitor-attack-surface')"
    :seo="[
        'title' => 'Monitor attack surface — Cywise',
        'description' => 'Keep track of public assets and exposed services as they change.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'en', 'case' => 'monitor-attack-surface'])
</x-layouts.website-v2>
