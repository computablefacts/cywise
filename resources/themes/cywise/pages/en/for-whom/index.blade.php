<?php

use function Laravel\Folio\name;

name('website.en.audiences.index');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.audiences.index')"
    :seo="[
        'title' => 'For Whom — Cywise',
        'description' => 'Choose the experience that matches your organization or role.',
    ]"
>
<main><section class="page-hero page-hero-blue"><div class="container-fluid shell"><span class="mono">FOR WHOM</span>
<h1>BUILT FOR TEAMS THAT NEED CLARITY.</h1><p>Choose the experience that matches your organization or role.</p></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="row g-4"><div class="col-md-6 col-xl-4"><a class="solution-card acid-card" href="{{ route('website.en.audiences.smbs') }}">
<span class="mono">01</span><h3>SMBS</h3><p>Security for small and medium businesses.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.en.audiences.startups') }}">
<span class="mono">02</span><h3>STARTUPS</h3><p>Security foundations for fast-growing teams.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card blue-card" href="{{ route('website.en.audiences.it-teams') }}">
<span class="mono">03</span><h3>IT TEAMS</h3><p>Prioritized security work for IT operations.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card pink-card" href="{{ route('website.en.audiences.cisos') }}">
<span class="mono">04</span><h3>CISOS</h3><p>Clear visibility for security leadership.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.en.audiences.msps') }}">
<span class="mono">05</span><h3>MSPS</h3><p>Repeatable security visibility for customers.</p><b>EXPLORE →</b></a></div></div></div></section></main>
</x-layouts.website-v2>