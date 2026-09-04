<?php

use function Laravel\Folio\name;

name('website.en.solutions.index');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.solutions.index')"
    :seo="[
        'title' => 'Solutions — Cywise',
        'description' => 'Choose the security capability that matches your current need.',
    ]"
>
<main><section class="page-hero page-hero-acid"><div class="container-fluid shell"><span class="mono">SOLUTIONS</span>
<h1>ONE PLATFORM. MULTIPLE DEFENSES.</h1><p>Choose the security capability that matches your current need.</p></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="row g-4"><div class="col-md-6 col-xl-4"><a class="solution-card acid-card" href="{{ route('website.en.solutions.attack-surface') }}">
<span class="mono">01</span><h3>ATTACK SURFACE</h3><p>See domains, services and public assets.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.en.solutions.vulnerability-management') }}">
<span class="mono">02</span><h3>VULNERABILITY MANAGEMENT</h3><p>Find and prioritize weaknesses.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card blue-card" href="{{ route('website.en.solutions.credential-monitoring') }}">
<span class="mono">03</span><h3>CREDENTIAL MONITORING</h3><p>Detect compromised company credentials.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card pink-card" href="{{ route('website.en.solutions.cyberbuddy') }}">
<span class="mono">04</span><h3>CYBERBUDDY</h3><p>Ask security questions from company context.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.en.solutions.pssi') }}">
<span class="mono">05</span><h3>PSSI</h3><p>Build a usable security policy.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card dark-card" href="{{ route('website.en.solutions.pentest') }}">
<span class="mono">06</span><h3>PENTEST</h3><p>Test critical systems with human experts.</p><b>EXPLORE →</b></a></div></div></div></section></main>
</x-layouts.website-v2>