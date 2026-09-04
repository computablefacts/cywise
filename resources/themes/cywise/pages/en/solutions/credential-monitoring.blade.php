<?php

use function Laravel\Folio\name;

name('website.en.solutions.credential-monitoring');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.solutions.credential-monitoring')"
    :seo="[
        'title' => 'Credential Monitoring — Cywise',
        'description' => 'Detect compromised company credentials before attackers use them.',
    ]"
>
<main>
<section class="page-hero page-hero-blue"><div class="container-fluid shell">
<span class="mono">SOLUTION / CREDENTIALS</span><h1>KNOW WHAT LEAKED.</h1><p>Detect compromised company credentials before attackers use them.</p>
<div class="d-flex flex-wrap gap-3 mt-4">
<a class="btn btn-dark-brutal btn-lg" href="{{ route('register') }}">START PROTECTING →</a>
<a class="btn btn-white-brutal btn-lg" href="{{ route('website.en.solutions.index') }}">EXPLORE CYWISE</a>
</div></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="section-heading">
<span class="mono">WHAT YOU GET</span><h2>SECURITY THAT STAYS CLEAR.</h2></div>
<div class="row g-4 mt-3"><div class="col-md-6 col-xl-4"><article class="solution-card acid-card">
<span class="mono">01 / FEATURE</span><h3>LEAK DETECTION</h3><p>Find exposed credentials linked to company identities.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">02 / FEATURE</span><h3>DOMAIN COVERAGE</h3><p>Monitor accounts connected to your domains.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card blue-card">
<span class="mono">03 / FEATURE</span><h3>ACTION GUIDANCE</h3><p>Know which password or account action comes first.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card pink-card">
<span class="mono">04 / FEATURE</span><h3>ONGOING CHECKS</h3><p>Keep monitoring after the first cleanup.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">05 / FEATURE</span><h3>TEAM ALERTS</h3><p>Surface new critical findings quickly.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card dark-card">
<span class="mono">06 / FEATURE</span><h3>CENTRAL VIEW</h3><p>Keep credential exposure with your other security risks.</p></article></div></div></div></section>
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