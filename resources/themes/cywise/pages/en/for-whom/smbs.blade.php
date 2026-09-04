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
<main>
<section class="page-hero page-hero-acid"><div class="container-fluid shell">
<span class="mono">FOR WHOM / SMBS</span><h1>SECURITY WITHOUT A LARGE SECURITY TEAM.</h1><p>Give small and medium businesses clear visibility and practical actions.</p>
<div class="d-flex flex-wrap gap-3 mt-4">
<a class="btn btn-dark-brutal btn-lg" href="{{ route('register') }}">START PROTECTING →</a>
<a class="btn btn-white-brutal btn-lg" href="{{ route('website.en.solutions.index') }}">EXPLORE CYWISE</a>
</div></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="section-heading">
<span class="mono">WHAT YOU GET</span><h2>SECURITY THAT STAYS CLEAR.</h2></div>
<div class="row g-4 mt-3"><div class="col-md-6 col-xl-4"><article class="solution-card acid-card">
<span class="mono">01 / FEATURE</span><h3>SIMPLE VISIBILITY</h3><p>See important exposure without specialist tools.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">02 / FEATURE</span><h3>PRIORITY</h3><p>Know what deserves attention first.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card blue-card">
<span class="mono">03 / FEATURE</span><h3>AFFORDABLE OPERATIONS</h3><p>Keep security work manageable for a small team.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card pink-card">
<span class="mono">04 / FEATURE</span><h3>CLEAR REPORTING</h3><p>Share security status with management.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">05 / FEATURE</span><h3>GUIDED FIXES</h3><p>Turn technical findings into direct actions.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card dark-card">
<span class="mono">06 / FEATURE</span><h3>CONTINUOUS PROTECTION</h3><p>Keep monitoring after the first assessment.</p></article></div></div></div></section>
<section class="detail-band"><div class="container-fluid shell"><div class="row g-0">
<div class="col-lg-4 detail-band-title"><span class="mono">HOW IT WORKS</span><h2>SEE.<br/>PRIORITIZE.<br/>ACT.</h2></div>
<div class="col-lg-8 detail-steps">
<div><b>01</b><h3>Connect your scope.</h3><p>Define what Cywise must monitor for your organization.</p></div>
<div><b>02</b><h3>Find the important risks.</h3><p>Cywise groups signals and highlights the findings that need action.</p></div>
<div><b>03</b><h3>Fix with clear guidance.</h3><p>Your team gets direct actions without unnecessary security complexity.</p></div>
</div></div></div></section>
<section class="page-cta"><div class="container-fluid shell text-center"><span class="mono">READY?</span>
<h2>MAKE YOUR SECURITY VISIBLE.</h2><a class="btn btn-dark-brutal btn-xl mt-4" href="{{ route('register') }}">START PROTECTING →</a>
</div></section></main>
</x-layouts.website-v2>