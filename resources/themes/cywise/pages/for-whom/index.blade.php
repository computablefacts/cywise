<?php

use function Laravel\Folio\name;

name('website.audiences.index');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.audiences.index')"
    :seo="[
        'title' => 'Pour qui — Cywise',
        'description' => 'Choisissez l’expérience adaptée à votre organisation ou votre rôle.',
    ]"
>
<main><section class="page-hero page-hero-blue"><div class="container-fluid shell"><span class="mono">POUR QUI</span>
<h1>CONÇU POUR LES ÉQUIPES QUI VEULENT DE LA CLARTÉ.</h1><p>Choisissez l’expérience adaptée à votre organisation ou votre rôle.</p></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="row g-4"><div class="col-md-6 col-xl-4"><a class="solution-card acid-card" href="{{ route('website.audiences.smbs') }}">
<span class="mono">01</span><h3>PME</h3><p>La sécurité pensée pour les petites et moyennes entreprises.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.audiences.startups') }}">
<span class="mono">02</span><h3>STARTUPS</h3><p>Des bases de sécurité solides pour les équipes en forte croissance.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card blue-card" href="{{ route('website.audiences.it-teams') }}">
<span class="mono">03</span><h3>ÉQUIPES IT</h3><p>Des actions de sécurité priorisées pour les équipes IT.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card pink-card" href="{{ route('website.audiences.cisos') }}">
<span class="mono">04</span><h3>RSSI</h3><p>Une visibilité claire pour les responsables de la sécurité.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.audiences.msps') }}">
<span class="mono">05</span><h3>MSP</h3><p>Une visibilité sécurité reproductible pour chaque client.</p><b>DÉCOUVRIR →</b></a></div></div></div></section></main>
</x-layouts.website-v2>