<?php

use function Laravel\Folio\name;

name('website.use-cases.index');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.index')"
    :seo="[
        'title' => 'Cas d’usage — Cywise',
        'description' => 'Partez du problème. Cywise le transforme en flux de sécurité clair.',
    ]"
>
<main><section class="page-hero page-hero-dark"><div class="container-fluid shell"><span class="mono">CAS D’USAGE</span>
<h1>QU’AVEZ-VOUS BESOIN DE FAIRE ?</h1><p>Partez du problème. Cywise le transforme en flux de sécurité clair.</p></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="row g-4"><div class="col-md-6 col-xl-4"><a class="solution-card acid-card" href="{{ route('website.use-cases.find-vulnerabilities') }}">
<span class="mono">01</span><h3>TROUVER DES VULNÉRABILITÉS</h3><p>Détectez et priorisez les faiblesses.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.use-cases.monitor-attack-surface') }}">
<span class="mono">02</span><h3>SURVEILLER LA SURFACE D’ATTAQUE</h3><p>Suivez l’exposition publique dans le temps.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card blue-card" href="{{ route('website.use-cases.check-leaked-credentials') }}">
<span class="mono">03</span><h3>VÉRIFIER LES IDENTIFIANTS COMPROMIS</h3><p>Détectez les identités compromises de l’entreprise.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card pink-card" href="{{ route('website.use-cases.create-pssi') }}">
<span class="mono">04</span><h3>CRÉER UNE PSSI</h3><p>Créez une politique de sécurité pragmatique.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card" href="{{ route('website.use-cases.prepare-audit') }}">
<span class="mono">05</span><h3>PRÉPARER UN AUDIT</h3><p>Organisez les preuves de sécurité et les écarts.</p><b>DÉCOUVRIR →</b></a></div><div class="col-md-6 col-xl-4"><a class="solution-card dark-card" href="{{ route('website.use-cases.run-pentest') }}">
<span class="mono">06</span><h3>LANCER UN PENTEST</h3><p>Éprouvez les systèmes critiques avec des experts.</p><b>DÉCOUVRIR →</b></a></div></div></div></section></main>
</x-layouts.website-v2>
