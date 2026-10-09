<?php

use function Laravel\Folio\name;

name('website.en.use-cases.check-leaked-credentials');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.use-cases.check-leaked-credentials')"
    :seo="[
        'title' => 'Check leaked credentials — Cywise',
        'description' => 'Detect exposed credentials and reduce account takeover risk.',
    ]"
>
@include('theme::partials.website-v2.use-case', ['locale' => 'en', 'case' => 'check-leaked-credentials'])
</x-layouts.website-v2>
