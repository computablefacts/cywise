<?php

use function Laravel\Folio\name;

name('website.en.use-cases.find-vulnerabilities');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.use-cases.find-vulnerabilities')"
    :seo="[
        'title' => 'Find vulnerabilities — Cywise',
        'description' => 'Discover weaknesses and turn them into a prioritized remediation list.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'en', 'case' => 'find-vulnerabilities'])
</x-layouts.website-v2>
