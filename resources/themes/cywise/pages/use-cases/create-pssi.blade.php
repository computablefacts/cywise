<?php

use function Laravel\Folio\name;

name('website.use-cases.create-pssi');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.create-pssi')"
    :seo="[
        'title' => 'Créer une PSSI — Cywise',
        'description' => 'Créez une politique de sécurité structurée adaptée à votre organisation.',
    ]"
>
<main>
<section class="page-hero page-hero-yellow"><div class="container-fluid shell">
<span class="mono">CAS D’USAGE / PSSI</span><h1>RÉDIGEZ UNE POLITIQUE QUE CHACUN PEUT APPLIQUER.</h1><p>Créez une politique de sécurité structurée adaptée à votre organisation.</p>
<div class="d-flex flex-wrap gap-3 mt-4">
<a class="btn btn-dark-brutal btn-lg" href="{{ route('register') }}">COMMENCER →</a>
<a class="btn btn-white-brutal btn-lg" href="{{ route('website.solutions.index') }}">DÉCOUVRIR CYWISE</a>
</div></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="section-heading">
<span class="mono">CE QUE VOUS OBTENEZ</span><h2>UNE SÉCURITÉ QUI RESTE CLAIRE.</h2></div>
<div class="row g-4 mt-3"><div class="col-md-6 col-xl-4"><article class="solution-card acid-card">
<span class="mono">01 / FONCTIONNALITÉ</span><h3>CADRE DE POLITIQUE</h3><p>Partez de domaines de sécurité concrets.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">02 / FONCTIONNALITÉ</span><h3>CONTEXTE</h3><p>Adaptez les contrôles à votre entreprise.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card blue-card">
<span class="mono">03 / FONCTIONNALITÉ</span><h3>RESPONSABILITÉS</h3><p>Définissez les responsables et les attentes.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card pink-card">
<span class="mono">04 / FONCTIONNALITÉ</span><h3>LANGAGE CLAIR</h3><p>Rendez les exigences claires pour les collaborateurs.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">05 / FONCTIONNALITÉ</span><h3>RÉVISION</h3><p>Maintenez la PSSI à jour.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card dark-card">
<span class="mono">06 / FONCTIONNALITÉ</span><h3>ACCOMPAGNEMENT</h3><p>Utilisez CyberBuddy pour clarifier les questions liées à la politique.</p></article></div></div></div></section>
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