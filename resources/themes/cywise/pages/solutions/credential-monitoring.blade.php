<?php

use function Laravel\Folio\name;

name('website.solutions.credential-monitoring');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.solutions.credential-monitoring')"
    :seo="[
        'title' => 'Surveillance des identifiants — Cywise',
        'description' => 'Détectez les identifiants compromis avant que les attaquants ne les utilisent.',
    ]"
>
<main>
<section class="page-hero page-hero-blue"><div class="container-fluid shell">
<span class="mono">SOLUTION / IDENTIFIANTS</span><h1>SACHEZ CE QUI A FUITÉ.</h1><p>Détectez les identifiants compromis avant que les attaquants ne les utilisent.</p>
<div class="d-flex flex-wrap gap-3 mt-4">
<a class="btn btn-dark-brutal btn-lg" href="{{ route('register') }}">COMMENCER →</a>
<a class="btn btn-white-brutal btn-lg" href="{{ route('website.solutions.index') }}">DÉCOUVRIR CYWISE</a>
</div></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="section-heading">
<span class="mono">CE QUE VOUS OBTENEZ</span><h2>UNE SÉCURITÉ QUI RESTE CLAIRE.</h2></div>
<div class="row g-4 mt-3"><div class="col-md-6 col-xl-4"><article class="solution-card acid-card">
<span class="mono">01 / FONCTIONNALITÉ</span><h3>DÉTECTION DES FUITES</h3><p>Trouvez les identifiants exposés liés aux comptes de l’entreprise.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">02 / FONCTIONNALITÉ</span><h3>COUVERTURE DES DOMAINES</h3><p>Surveillez les comptes liés à vos domaines.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card blue-card">
<span class="mono">03 / FONCTIONNALITÉ</span><h3>CONSEILS D’ACTION</h3><p>Sachez quel mot de passe ou compte traiter en premier.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card pink-card">
<span class="mono">04 / FONCTIONNALITÉ</span><h3>CONTRÔLES CONTINUS</h3><p>Continuez la surveillance après le premier nettoyage.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">05 / FONCTIONNALITÉ</span><h3>ALERTES ÉQUIPE</h3><p>Faites remonter rapidement les nouveaux constats critiques.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card dark-card">
<span class="mono">06 / FONCTIONNALITÉ</span><h3>VUE CENTRALE</h3><p>Regroupez l’exposition des identifiants avec vos autres risques de sécurité.</p></article></div></div></div></section>
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