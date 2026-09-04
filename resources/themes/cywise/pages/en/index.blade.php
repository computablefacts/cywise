<?php

use App\Services\BlogContent;
use Illuminate\View\View;
use function Laravel\Folio\{name, render};

name('website.en.home');

render(function (View $view, BlogContent $content) {
    return $view->with('posts', $content->homePosts());
});
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('home')"
    :seo="[
        'title' => 'Cywise — Cybersecurity for Humans',
        'description' => 'Cywise helps companies identify exposed assets, vulnerabilities and compromised credentials.',
    ]"
>
<main id="top">
<section class="hero section-pad">
<div class="container-fluid shell">
<div class="row g-5 align-items-center">
<div class="col-lg-7">
<div class="eyebrow mono mb-4">CYBERSECURITY FOR HUMANS / 01</div>
<h1 class="display-monster">
              YOUR COMPANY<br/>
              IS ALREADY<br/>
<span class="marker">A TARGET.</span>
</h1>
<p class="hero-lead">
              Cywise finds your exposed assets, vulnerabilities and leaked credentials
              before attackers do.
            </p>
<div class="d-flex flex-wrap gap-3 mt-4">
<a class="btn btn-acid btn-lg" href="#cta">START PROTECTING →</a>
<a class="btn btn-ghost-brutal btn-lg" href="#solutions">EXPLORE CYWISE</a>
</div>
<div class="hero-stats row g-0 mt-5">
<div class="col-6 col-md-4 stat-box">
<strong>24/7</strong>
<span>MONITORING</span>
</div>
<div class="col-6 col-md-4 stat-box">
<strong>1K+</strong>
<span>SERVERS</span>
</div>
<div class="col-12 col-md-4 stat-box">
<strong>170B+</strong>
<span>CREDENTIALS</span>
</div>
</div>
</div>
<div class="col-lg-5">
<div class="dashboard-shell">
<div class="dash-titlebar">
<span class="mono">CYWISE / LIVE</span>
<span class="blink-dot"></span>
</div>
<div class="dash-body">
<div class="d-flex justify-content-between align-items-end mb-4">
<div>
<span class="mono small">SECURITY SCORE</span>
<div class="score">72<span>/100</span></div>
</div>
<div class="risk-badge">↑ 12 RISKS</div>
</div>
<div class="risk-row">
<div><span class="risk risk-critical">CRITICAL</span> Exposed admin portal</div>
<strong>01</strong>
</div>
<div class="risk-row">
<div><span class="risk risk-high">HIGH</span> CVE on public server</div>
<strong>04</strong>
</div>
<div class="risk-row">
<div><span class="risk risk-medium">MEDIUM</span> TLS configuration</div>
<strong>07</strong>
</div>
<div class="mini-chart mt-4">
<span style="height:35%"></span>
<span style="height:55%"></span>
<span style="height:42%"></span>
<span style="height:78%"></span>
<span style="height:62%"></span>
<span style="height:88%"></span>
<span style="height:71%"></span>
</div>
</div>
</div>
<div class="floating-note mono">
              ATTACKERS SEE IT.<br/>NOW YOU DO TOO. ↗
            </div>
</div>
</div>
</div>
</section>
<section class="manifesto border-y" id="problem">
<div class="container-fluid shell">
<div class="row g-0">
<div class="col-lg-5 manifesto-title">
<span class="mono">THE PROBLEM / 02</span>
<h2>SECURITY SHOULD NOT NEED A 50-PERSON TEAM.</h2>
</div>
<div class="col-lg-7 manifesto-grid">
<div class="principle">
<span class="mono">01 / VISIBILITY</span>
<h3>KNOW WHAT IS EXPOSED.</h3>
</div>
<div class="principle principle-blue">
<span class="mono">02 / PRIORITY</span>
<h3>KNOW WHAT MATTERS.</h3>
</div>
<div class="principle principle-pink">
<span class="mono">03 / ACTION</span>
<h3>KNOW WHAT TO FIX.</h3>
</div>
</div>
</div>
</div>
</section>
<section class="section-pad" id="solutions">
<div class="container-fluid shell">
<div class="section-heading">
<span class="mono">SOLUTIONS / 03</span>
<h2>ONE PLATFORM.<br/>MULTIPLE DEFENSES.</h2>
<p>Simple tools for the security work your company must do every day.</p>
</div>
<div class="row g-4 mt-2">
<div class="col-md-6 col-xl-4">
<article class="solution-card acid-card">
<span class="mono">01 / ATTACK SURFACE</span>
<h3>SEE WHAT ATTACKERS SEE.</h3>
<p>Monitor public domains, servers, ports and exposed services.</p>
<a href="{{ route('website.en.solutions.attack-surface') }}">EXPLORE →</a>
</article>
</div>
<div class="col-md-6 col-xl-4">
<article class="solution-card">
<span class="mono">02 / VULNERABILITIES</span>
<h3>FIND THE WEAK POINTS.</h3>
<p>Identify vulnerabilities and focus on the most important risks.</p>
<a href="{{ route('website.en.solutions.vulnerability-management') }}">EXPLORE →</a>
</article>
</div>
<div class="col-md-6 col-xl-4">
<article class="solution-card blue-card">
<span class="mono">03 / CREDENTIALS</span>
<h3>KNOW WHAT LEAKED.</h3>
<p>Detect compromised credentials linked to your company.</p>
<a href="{{ route('website.en.solutions.credential-monitoring') }}">EXPLORE →</a>
</article>
</div>
<div class="col-md-6 col-xl-4">
<article class="solution-card pink-card">
<span class="mono">04 / CYBERBUDDY</span>
<h3>ASK. UNDERSTAND. ACT.</h3>
<p>Get clear cybersecurity guidance from your company context.</p>
<a href="{{ route('website.en.solutions.cyberbuddy') }}">EXPLORE →</a>
</article>
</div>
<div class="col-md-6 col-xl-4">
<article class="solution-card">
<span class="mono">05 / PSSI</span>
<h3>BUILD YOUR SECURITY POLICY.</h3>
<p>Create a practical security policy for your organization.</p>
<a href="{{ route('website.en.solutions.pssi') }}">EXPLORE →</a>
</article>
</div>
<div class="col-md-6 col-xl-4">
<article class="solution-card dark-card">
<span class="mono">06 / PENTEST</span>
<h3>PUT HUMANS ON THE ATTACK.</h3>
<p>Test critical applications with experienced security experts.</p>
<a href="{{ route('website.en.solutions.pentest') }}">EXPLORE →</a>
</article>
</div>
</div>
</div>
</section>
<section class="cyberbuddy border-y" id="cyberbuddy">
<div class="container-fluid shell">
<div class="row g-0">
<div class="col-lg-5 buddy-copy">
<span class="mono">CYBERBUDDY / 04</span>
<h2>YOUR CYBERSECURITY TEAM GOT AN AI COLLEAGUE.</h2>
<p>
              Ask direct questions about risks, policies and security actions.
            </p>
<a class="btn btn-dark-brutal mt-3" href="{{ route('website.en.solutions.cyberbuddy') }}">MEET CYBERBUDDY →</a>
</div>
<div class="col-lg-7 buddy-chat">
<div class="chat-window">
<div class="chat-top mono">CYBERBUDDY / COMPANY CONTEXT ON</div>
<div class="chat-message user-message">
<span>YOU</span>
                Are any of our public servers vulnerable?
              </div>
<div class="chat-message bot-message">
<span>CYBERBUDDY</span>
                Yes. Three findings require action.
                <div class="finding"><b>CRITICAL</b> Exposed administration portal</div>
<div class="finding"><b>HIGH</b> Outdated web component</div>
<div class="finding"><b>MEDIUM</b> TLS configuration</div>
</div>
<div class="chat-input">Ask Cywise anything... <strong>↵</strong></div>
</div>
</div>
</div>
</div>
</section>
<section class="section-pad" id="for-whom">
<div class="container-fluid shell">
<div class="section-heading split-heading">
<div>
<span class="mono">FOR WHOM / 05</span>
<h2>BUILT FOR TEAMS THAT NEED CLARITY.</h2>
</div>
<p>Choose the view that matches your organization and security role.</p>
</div>
<div class="row g-3 mt-3">
<div class="col-md-6 col-xl-3"><a class="audience-card" href="{{ route('website.en.audiences.smbs') }}"><span>01</span><h3>SMBs</h3><p>Protect the business without heavy security operations.</p><b>→</b></a></div>
<div class="col-md-6 col-xl-3"><a class="audience-card" href="{{ route('website.en.audiences.it-teams') }}"><span>02</span><h3>IT TEAMS</h3><p>See risks clearly and know what to fix first.</p><b>→</b></a></div>
<div class="col-md-6 col-xl-3"><a class="audience-card" href="{{ route('website.en.audiences.cisos') }}"><span>03</span><h3>CISOs</h3><p>Centralize visibility and support security decisions.</p><b>→</b></a></div>
<div class="col-md-6 col-xl-3"><a class="audience-card" href="{{ route('website.en.audiences.msps') }}"><span>04</span><h3>MSPs</h3><p>Manage security visibility across customer environments.</p><b>→</b></a></div>
</div>
</div>
</section>
<section class="use-cases" id="use-cases">
<div class="container-fluid shell">
<div class="use-intro">
<span class="mono">USE CASES / 06</span>
<h2>WHAT DO YOU NEED TO DO?</h2>
</div>
<div class="case-list">
<a href="{{ route('website.en.use-cases.find-vulnerabilities') }}"><span>01</span> FIND MY VULNERABILITIES <b>↗</b></a>
<a href="{{ route('website.en.use-cases.monitor-attack-surface') }}"><span>02</span> MONITOR MY ATTACK SURFACE <b>↗</b></a>
<a href="{{ route('website.en.use-cases.check-leaked-credentials') }}"><span>03</span> CHECK LEAKED CREDENTIALS <b>↗</b></a>
<a href="{{ route('website.en.use-cases.create-pssi') }}"><span>04</span> CREATE MY PSSI <b>↗</b></a>
<a href="{{ route('website.en.use-cases.prepare-audit') }}"><span>05</span> PREPARE FOR AN AUDIT <b>↗</b></a>
<a href="{{ route('website.en.use-cases.run-pentest') }}"><span>06</span> RUN A PENTEST <b>↗</b></a>
</div>
</div>
</section>
<section class="pentest section-pad border-y" id="pentest">
<div class="container-fluid shell">
<div class="row g-5 align-items-center">
<div class="col-lg-7">
<span class="mono">PENTEST / 07</span>
<h2>SOMETIMES YOU NEED A HUMAN.</h2>
<p class="lead-copy">Expert testing for critical applications and infrastructure.</p>
<a class="btn btn-acid btn-lg" href="{{ route('website.en.solutions.pentest') }}">BOOK A PENTEST →</a>
</div>
<div class="col-lg-5">
<div class="pentest-sheet">
<div><span>ENGAGEMENT</span><strong>4 DAYS</strong></div>
<div><span>DELIVERABLE</span><strong>1 REPORT</strong></div>
<div><span>STARTING AT</span><strong>€3,000</strong></div>
<div class="stamp">HUMAN<br/>VERIFIED</div>
</div>
</div>
</div>
</div>
</section>
<section class="section-pad" id="resources">
<div class="container-fluid shell">
<div class="section-heading split-heading">
<div>
<span class="mono">RESOURCES / 08</span>
<h2>FROM THE CYWISE LAB.</h2>
</div>
<a class="btn btn-ghost-brutal" href="{{ route('blog.en') }}">VIEW ALL →</a>
</div>
<div class="row g-4 mt-3">
@include('theme::partials.website-v2.posts-loop', ['posts' => $posts, 'locale' => 'en'])
</div>
</div>
</section>
<section class="pricing border-y" id="pricing">
<div class="container-fluid shell">
<div class="pricing-wrap">
<div>
<span class="mono">PRICING / 09</span>
<h2>SECURITY WITHOUT ENTERPRISE COMPLEXITY.</h2>
</div>
<div class="pricing-card">
<span class="mono">CYWISE PLATFORM</span>
<h3>START SIMPLE.</h3>
<p>Monitor your company. Find risks. Take action.</p>
<a class="btn btn-dark-brutal w-100" href="{{ route('website.en.pricing') }}">SEE PRICING →</a>
</div>
</div>
</div>
</section>
<section class="final-cta" id="cta">
<div class="container-fluid shell text-center">
<span class="mono">READY / 10</span>
<h2>YOU DO NOT NEED MORE CYBERSECURITY COMPLEXITY.</h2>
<h3>YOU NEED CYWISE.</h3>
<div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
<a class="btn btn-dark-brutal btn-xl" href="{{ route('register') }}">START FOR FREE →</a>
<a class="btn btn-white-brutal btn-xl" href="mailto:{{ config('towerify.freshdesk.from_email') }}">BOOK A DEMO</a>
</div>
</div>
</section>
</main>
</x-layouts.website-v2>
