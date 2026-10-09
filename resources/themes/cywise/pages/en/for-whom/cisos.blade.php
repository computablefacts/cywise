<?php

use function Laravel\Folio\name;

name('website.en.audiences.cisos');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.audiences.cisos')"
    :seo="[
        'title' => 'CISOs — Cywise',
        'description' => 'Give security leaders a clear view of exposure, priority and progress.',
    ]"
>
@include('theme::partials.website-v2.audience', ['locale' => 'en', 'audience' => 'cisos'])
</x-layouts.website-v2>
