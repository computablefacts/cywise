<?php

use function Laravel\Folio\name;

name('website.en.use-cases.create-pssi');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.use-cases.create-pssi')"
    :seo="[
        'title' => 'Create a PSSI — Cywise',
        'description' => 'Create a structured security policy that fits your organization.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'en', 'case' => 'create-pssi'])
</x-layouts.website-v2>
