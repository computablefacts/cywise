<?php

use function Laravel\Folio\name;

name('website.use-cases.monitor-attack-surface');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.use-cases.monitor-attack-surface')"
    :seo="[
        'title' => 'Surveiller la surface d’attaque — Cywise',
        'description' => 'Suivez les actifs publics et les services exposés lorsqu’ils évoluent.',
    ]"
>
<main>
<section class="page-hero page-hero-blue"><div class="container-fluid shell">
<span class="mono">CAS D’USAGE / SURFACE D’ATTAQUE</span><h1>VISUALISEZ VOTRE EMPREINTE EXTERNE.</h1><p>Suivez les actifs publics et les services exposés lorsqu’ils évoluent.</p>
<div class="d-flex flex-wrap gap-3 mt-4">
<a class="btn btn-dark-brutal btn-lg" href="{{ route('register') }}">COMMENCER →</a>
<a class="btn btn-white-brutal btn-lg" href="{{ route('website.solutions.index') }}">DÉCOUVRIR CYWISE</a>
</div></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="section-heading">
<span class="mono">CE QUE VOUS OBTENEZ</span><h2>UNE SÉCURITÉ QUI RESTE CLAIRE.</h2></div>
<div class="row g-4 mt-3"><div class="col-md-6 col-xl-4"><article class="solution-card acid-card">
<span class="mono">01 / FONCTIONNALITÉ</span><h3>DÉCOUVERTE DES ACTIFS</h3><p>Identifiez les domaines, hôtes et services publics.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">02 / FONCTIONNALITÉ</span><h3>DÉTECTION DES CHANGEMENTS</h3><p>Détectez l’apparition de nouvelles expositions.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card blue-card">
<span class="mono">03 / FONCTIONNALITÉ</span><h3>REVUE DES SERVICES</h3><p>Comprenez ce qui est accessible depuis Internet.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card pink-card">
<span class="mono">04 / FONCTIONNALITÉ</span><h3>RÉDUCTION DES INCONNUES</h3><p>Retrouvez les actifs oubliés ou non gérés.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">05 / FONCTIONNALITÉ</span><h3>PRIORISATION DE L’EXPOSITION</h3><p>Concentrez-vous sur les services publics à risque.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card dark-card">
<span class="mono">06 / FONCTIONNALITÉ</span><h3>CONSERVATION DE L’HISTORIQUE</h3><p>Suivez l’évolution de votre empreinte.</p></article></div></div></div></section>
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