<?php

use function Laravel\Folio\name;

name('website.audiences.msps');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.audiences.msps')"
    :seo="[
        'title' => 'MSP — Cywise',
        'description' => 'Standardisez la visibilité et les conseils sécurité sur les environnements clients.',
    ]"
>
<main>
<section class="page-hero page-hero-yellow"><div class="container-fluid shell">
<span class="mono">POUR QUI / MSP</span><h1>PROTÉGEZ PLUS DE CLIENTS, PLUS SIMPLEMENT.</h1><p>Standardisez la visibilité et les conseils sécurité sur les environnements clients.</p>
<div class="d-flex flex-wrap gap-3 mt-4">
<a class="btn btn-dark-brutal btn-lg" href="{{ route('register') }}">COMMENCER →</a>
<a class="btn btn-white-brutal btn-lg" href="{{ route('website.solutions.index') }}">DÉCOUVRIR CYWISE</a>
</div></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="section-heading">
<span class="mono">CE QUE VOUS OBTENEZ</span><h2>UNE SÉCURITÉ QUI RESTE CLAIRE.</h2></div>
<div class="row g-4 mt-3"><div class="col-md-6 col-xl-4"><article class="solution-card acid-card">
<span class="mono">01 / FONCTIONNALITÉ</span><h3>VUE MULTI-CLIENTS</h3><p>Gardez les périmètres clients bien organisés.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">02 / FONCTIONNALITÉ</span><h3>FLUX RÉPÉTABLES</h3><p>Appliquez le même processus de gestion des risques à tous les comptes.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card blue-card">
<span class="mono">03 / FONCTIONNALITÉ</span><h3>REPORTING CLAIR</h3><p>Fournissez à vos clients des bilans de sécurité clairs.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card pink-card">
<span class="mono">04 / FONCTIONNALITÉ</span><h3>PRIORITÉ</h3><p>Concentrez les efforts là où ils ont le plus d’impact.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">05 / FONCTIONNALITÉ</span><h3>PASSAGE À L’ÉCHELLE</h3><p>Réduisez les analyses manuelles répétitives.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card dark-card">
<span class="mono">06 / FONCTIONNALITÉ</span><h3>EXTENSION DE SERVICE</h3><p>Ajoutez une visibilité sécurité à vos services IT managés.</p></article></div></div></div></section>
<section class="detail-band"><div class="container-fluid shell"><div class="row g-0">
<div class="col-lg-4 detail-band-title"><span class="mono">COMMENT ÇA MARCHE</span><h2>VOYEZ.<br/>PRIORISEZ.<br/>AGISSEZ.</h2></div>
<div class="col-lg-8 detail-steps">
<div><b>01</b><h3>Définissez votre périmètre.</h3><p>Définissez ce que Cywise doit surveiller pour votre organisation.</p></div>
<div><b>02</b><h3>Trouvez les risques importants.</h3><p>Cywise regroupe les signaux et met en évidence les constats qui nécessitent une action.</p></div>
<div><b>03</b><h3>Corrigez avec des conseils clairs.</h3><p>Votre équipe reçoit des actions concrètes, sans complexité inutile.</p></div>
</div></div></div></section>
<section class="page-cta"><div class="container-fluid shell text-center"><span class="mono">PRÊT ?</span>
<h2>RENDEZ VOTRE SÉCURITÉ VISIBLE.</h2><a class="btn btn-dark-brutal btn-xl mt-4" href="{{ route('register') }}">COMMENCER →</a>
</div></section></main>
</x-layouts.website-v2>