<?php

use function Laravel\Folio\name;

name('website.en.use-cases.prepare-audit');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.use-cases.prepare-audit')"
    :seo="[
        'title' => 'Prepare for an audit — Cywise',
        'description' => 'Organize findings, policies and progress before an audit or customer review.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'en', 'case' => 'prepare-audit'])
</x-layouts.website-v2>
