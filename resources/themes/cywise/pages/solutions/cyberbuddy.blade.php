<?php

use function Laravel\Folio\name;

name('website.solutions.cyberbuddy');
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.solutions.cyberbuddy')"
    :seo="[
        'title' => 'CyberBuddy — Cywise',
        'description' => 'Utilisez un assistant sécurité qui répond selon le contexte de votre entreprise.',
    ]"
>
<main>
<section class="page-hero page-hero-pink"><div class="container-fluid shell">
<span class="mono">SOLUTION / CYBERBUDDY</span><h1>DEMANDEZ. COMPRENEZ. AGISSEZ.</h1><p>Utilisez un assistant sécurité qui répond selon le contexte de votre entreprise.</p>
<div class="d-flex flex-wrap gap-3 mt-4">
<a class="btn btn-dark-brutal btn-lg" href="{{ route('register') }}">COMMENCER →</a>
<a class="btn btn-white-brutal btn-lg" href="{{ route('website.solutions.index') }}">DÉCOUVRIR CYWISE</a>
</div></div></section>
<section class="section-pad"><div class="container-fluid shell"><div class="section-heading">
<span class="mono">CE QUE VOUS OBTENEZ</span><h2>UNE SÉCURITÉ QUI RESTE CLAIRE.</h2></div>
<div class="row g-4 mt-3"><div class="col-md-6 col-xl-4"><article class="solution-card acid-card">
<span class="mono">01 / FONCTIONNALITÉ</span><h3>QUESTIONS DIRECTES</h3><p>Posez des questions de sécurité en langage simple.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">02 / FONCTIONNALITÉ</span><h3>CONTEXTE ENTREPRISE</h3><p>Utilisez vos politiques et données de sécurité pour obtenir des réponses pertinentes.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card blue-card">
<span class="mono">03 / FONCTIONNALITÉ</span><h3>CONSEILS D’ACTION</h3><p>Transformez les constats en prochaines étapes concrètes.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card pink-card">
<span class="mono">04 / FONCTIONNALITÉ</span><h3>ACCOMPAGNEMENT DES POLITIQUES</h3><p>Obtenez de l’aide pour la documentation sécurité et la PSSI.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card">
<span class="mono">05 / FONCTIONNALITÉ</span><h3>EXPLICATION DU RISQUE</h3><p>Expliquez les constats techniques aux non-spécialistes.</p></article></div><div class="col-md-6 col-xl-4"><article class="solution-card dark-card">
<span class="mono">06 / FONCTIONNALITÉ</span><h3>ASSISTANCE QUOTIDIENNE</h3><p>Donnez aux équipes IT une référence sécurité rapide.</p></article></div></div></div></section>
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