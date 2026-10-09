<?php

use function Laravel\Folio\name;

name('website.en.audiences.it-teams');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.audiences.it-teams')"
    :seo="[
        'title' => 'IT Teams — Cywise',
        'description' => 'Give IT teams a direct security worklist.',
    ]"
>
@include('theme::partials.website-v2.audience', ['locale' => 'en', 'audience' => 'it-teams'])
</x-layouts.website-v2>
