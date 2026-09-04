<?php

use function Laravel\Folio\name;

name('website.en.use-cases.index');
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('website.use-cases.index')"
    :seo="[
        'title' => 'Use Cases — Cywise',
        'description' => 'Start with the problem. Cywise maps it to a clear security workflow.',
    ]"
>
<main><section class="page-hero page-hero-dark"><div class="container-fluid shell"><span class="mono">USE CASES</span>
<h1>WHAT DO YOU NEED TO DO?</h1><p>Start with the problem. Cywise maps it to a clear security workflow.</p></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="row g-4"><div class="col-md-6 col-xl-4"><a class="solution-card acid-card" href="{{ route('website.en.use-cases.find-vulnerabilities') }}">
<span class="mono">01</span><h3>FIND VULNERABILITIES</h3><p>Discover and prioritize weaknesses.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.en.use-cases.monitor-attack-surface') }}">
<span class="mono">02</span><h3>MONITOR ATTACK SURFACE</h3><p>Track public exposure over time.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card blue-card" href="{{ route('website.en.use-cases.check-leaked-credentials') }}">
<span class="mono">03</span><h3>CHECK LEAKED CREDENTIALS</h3><p>Detect compromised company identities.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card pink-card" href="{{ route('website.en.use-cases.create-pssi') }}">
<span class="mono">04</span><h3>CREATE A PSSI</h3><p>Build a practical security policy.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.en.use-cases.prepare-audit') }}">
<span class="mono">05</span><h3>PREPARE FOR AN AUDIT</h3><p>Organize security evidence and gaps.</p><b>EXPLORE →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card dark-card" href="{{ route('website.en.use-cases.run-pentest') }}">
<span class="mono">06</span><h3>RUN A PENTEST</h3><p>Challenge critical systems with experts.</p><b>EXPLORE →</b></a></div></div></div></section></main>
</x-layouts.website-v2>