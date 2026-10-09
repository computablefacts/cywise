<?php

use function Laravel\Folio\name;

name('website.en.audiences.smbs');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.audiences.smbs')"
    :seo="[
        'title' => 'SMBs — Cywise',
        'description' => 'Give small and medium businesses clear visibility and practical actions.',
    ]"
>
@include('theme::partials.website-v2.audience', ['locale' => 'en', 'audience' => 'smbs'])
</x-layouts.website-v2>
