<?php

use function Laravel\Folio\name;

name('website.solutions.index');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.solutions.index')"
    :seo="[
        'title' => 'Solutions — Cywise',
        'description' => 'Choisissez la capacité de sécurité adaptée à votre besoin actuel.',
    ]"
>
<main><section class="page-hero page-hero-acid"><div class="container-fluid shell"><span class="mono">SOLUTIONS</span>
<h1>UNE PLATEFORME. PLUSIEURS DÉFENSES.</h1><p>Choisissez la capacité de sécurité adaptée à votre besoin actuel.</p></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="row g-4"><div class="col-md-6 col-xl-4"><a class="solution-card acid-card" href="{{ route('website.solutions.attack-surface') }}">
<span class="mono">01</span><h3>SURFACE D’ATTAQUE</h3><p>Visualisez les domaines, services et actifs publics.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.solutions.vulnerability-management') }}">
<span class="mono">02</span><h3>GESTION DES VULNÉRABILITÉS</h3><p>Détectez et priorisez les faiblesses.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card blue-card" href="{{ route('website.solutions.credential-monitoring') }}">
<span class="mono">03</span><h3>SURVEILLANCE DES IDENTIFIANTS</h3><p>Détectez les identifiants compromis de l’entreprise.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card pink-card" href="{{ route('website.solutions.cyberbuddy') }}">
<span class="mono">04</span><h3>CYBERBUDDY</h3><p>Posez vos questions de sécurité dans le contexte de votre entreprise.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.solutions.pssi') }}">
<span class="mono">05</span><h3>PSSI</h3><p>Créez une politique de sécurité réellement applicable.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card dark-card" href="{{ route('website.solutions.pentest') }}">
<span class="mono">06</span><h3>PENTEST</h3><p>Faites tester les systèmes critiques par des experts.</p><b>DÉCOUVRIR →</b></a></div></div></div></section></main>
</x-layouts.website-v2>