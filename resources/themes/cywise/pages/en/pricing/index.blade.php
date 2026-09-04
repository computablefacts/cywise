<?php

use function Laravel\Folio\{name};

name('website.en.pricing');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('pricing')"
    :seo="[
        'title' => 'Pricing — Cywise',
        'description' => 'Simple Cywise pricing concept for growing teams.',
    ]"
>
<main><section class="page-hero page-hero-acid"><div class="container-fluid shell"><span class="mono">PRICING</span><h1>SECURITY WITHOUT ENTERPRISE COMPLEXITY.</h1><p>Start with clear visibility. Add expert testing when you need it.</p></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="row g-4 justify-content-center">
@include('theme::partials.website-v2.plans', ['locale' => 'en'])
</div></div></section></main>
</x-layouts.website-v2>
