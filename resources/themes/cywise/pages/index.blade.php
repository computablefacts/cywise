<?php

use App\Services\BlogContent;
use Illuminate\View\View;
use function Laravel\Folio\{name, render};

name('home');

render(function (View $view, BlogContent $content) {
    return $view->with('posts', $content->homePosts());
});
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.home')"
    :seo="[
        'title' => 'Cywise — La cybersécurité pour tous',
        'description' => 'Cywise aide les entreprises à détecter leurs actifs exposés, leurs vulnérabilités et leurs identifiants compromis.',
    ]"
>
<main id="top">
<section class="hero section-pad">
<div class="container-fluid shell">
<div class="row g-5 align-items-center">
<div class="col-lg-7">
<div class="eyebrow mono mb-4">LA CYBERSÉCURITÉ POUR TOUS / 01</div>
<h1 class="display-monster">
              VOTRE ENTREPRISE<br/>
              EST DÉJÀ<br/>
<span class="marker">UNE CIBLE.</span>
</h1>
<p class="hero-lead">
              Cywise détecte vos actifs exposés, vos vulnérabilités et vos identifiants compromis avant les attaquants.
            </p>
<div class="d-flex flex-wrap gap-3 mt-4">
<a class="btn btn-acid btn-lg" href="#cta">COMMENCER →</a>
<a class="btn btn-ghost-brutal btn-lg" href="#solutions">DÉCOUVRIR CYWISE</a>
</div>
<div class="hero-stats row g-0 mt-5">
<div class="col-6 col-md-4 stat-box">
<strong>24/7</strong>
<span>SURVEILLANCE</span>
</div>
<div class="col-6 col-md-4 stat-box">
<strong>1K+</strong>
<span>SERVEURS</span>
</div>
<div class="col-12 col-md-4 stat-box">
<strong>170B+</strong>
<span>IDENTIFIANTS</span>
</div>
</div>
</div>
<div class="col-lg-5">
<div class="dashboard-shell">
<div class="dash-titlebar">
<span class="mono">CYWISE / EN DIRECT</span>
<span class="blink-dot"></span>
</div>
<div class="dash-body">
<div class="d-flex justify-content-between align-items-end mb-4">
<div>
<span class="mono small">SCORE DE SÉCURITÉ</span>
<div class="score">72<span>/100</span></div>
</div>
<div class="risk-badge">↑ 12 RISQUES</div>
</div>
<div class="risk-row">
<div><span class="risk risk-critical">CRITIQUE</span> Portail d’administration exposé</div>
<strong>01</strong>
</div>
<div class="risk-row">
<div><span class="risk risk-high">ÉLEVÉ</span> CVE sur serveur public</div>
<strong>04</strong>
</div>
<div class="risk-row">
<div><span class="risk risk-medium">MOYEN</span> Configuration TLS</div>
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
              LES ATTAQUANTS LE VOIENT.<br/>VOUS AUSSI, MAINTENANT. ↗
            </div>
</div>
</div>
</div>
</section>
<section class="manifesto border-y" id="problem">
<div class="container-fluid shell">
<div class="row g-0">
<div class="col-lg-5 manifesto-title">
<span class="mono">LE PROBLÈME / 02</span>
<h2>LA CYBERSÉCURITÉ NE DEVRAIT PAS EXIGER UNE ÉQUIPE DE 50 PERSONNES.</h2>
</div>
<div class="col-lg-7 manifesto-grid">
<div class="principle">
<span class="mono">01 / VISIBILITÉ</span>
<h3>SACHEZ CE QUI EST EXPOSÉ.</h3>
</div>
<div class="principle principle-blue">
<span class="mono">02 / PRIORITÉ</span>
<h3>SACHEZ CE QUI COMPTE.</h3>
</div>
<div class="principle principle-pink">
<span class="mono">03 / ACTION</span>
<h3>SACHEZ QUOI CORRIGER.</h3>
</div>
</div>
</div>
</div>
</section>
<section class="section-pad" id="solutions">
<div class="container-fluid shell">
<div class="section-heading">
<span class="mono">SOLUTIONS / 03</span>
<h2>UNE PLATEFORME.<br/>PLUSIEURS DÉFENSES.</h2>
<p>Des outils simples pour les tâches de sécurité que votre entreprise doit gérer chaque jour.</p>
</div>
<div class="row g-4 mt-2">
<div class="col-md-6 col-xl-4">
<article class="solution-card acid-card">
<span class="mono">01 / SURFACE D’ATTAQUE</span>
<h3>VOYEZ CE QUE LES ATTAQUANTS VOIENT.</h3>
<p>Surveillez les domaines publics, serveurs, ports et services exposés.</p>
<a href="{{ route('website.solutions.attack-surface') }}">DÉCOUVRIR →</a>
</article>
</div>
<div class="col-md-6 col-xl-4">
<article class="solution-card">
<span class="mono">02 / VULNÉRABILITÉS</span>
<h3>TROUVEZ LES POINTS FAIBLES.</h3>
<p>Identifiez les vulnérabilités et concentrez-vous sur les risques les plus importants.</p>
<a href="{{ route('website.solutions.vulnerability-management') }}">DÉCOUVRIR →</a>
</article>
</div>
<div class="col-md-6 col-xl-4">
<article class="solution-card blue-card">
<span class="mono">03 / IDENTIFIANTS</span>
<h3>SACHEZ CE QUI A FUITÉ.</h3>
<p>Détectez les identifiants compromis liés à votre entreprise.</p>
<a href="{{ route('website.solutions.credential-monitoring') }}">DÉCOUVRIR →</a>
</article>
</div>
<div class="col-md-6 col-xl-4">
<article class="solution-card pink-card">
<span class="mono">04 / CYBERBUDDY</span>
<h3>DEMANDEZ. COMPRENEZ. AGISSEZ.</h3>
<p>Obtenez des conseils clairs adaptés au contexte de votre entreprise.</p>
<a href="{{ route('website.solutions.cyberbuddy') }}">DÉCOUVRIR →</a>
</article>
</div>
<div class="col-md-6 col-xl-4">
<article class="solution-card">
<span class="mono">05 / PSSI</span>
<h3>CRÉEZ VOTRE POLITIQUE DE SÉCURITÉ.</h3>
<p>Créez une politique de sécurité pratique pour votre organisation.</p>
<a href="{{ route('website.solutions.pssi') }}">DÉCOUVRIR →</a>
</article>
</div>
<div class="col-md-6 col-xl-4">
<article class="solution-card dark-card">
<span class="mono">06 / PENTEST</span>
<h3>FAITES TESTER PAR DES EXPERTS.</h3>
<p>Faites tester vos applications critiques par des experts en sécurité.</p>
<a href="{{ route('website.solutions.pentest') }}">DÉCOUVRIR →</a>
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
<h2>VOTRE ÉQUIPE CYBERSÉCURITÉ A UN COLLÈGUE IA.</h2>
<p>
              Posez des questions directes sur les risques, les politiques et les actions de sécurité.
            </p>
<a class="btn btn-dark-brutal mt-3" href="{{ route('website.solutions.cyberbuddy') }}">DÉCOUVRIR CYBERBUDDY →</a>
</div>
<div class="col-lg-7 buddy-chat">
<div class="chat-window">
<div class="chat-top mono">CYBERBUDDY / CONTEXTE ENTREPRISE ACTIF</div>
<div class="chat-message user-message">
<span>VOUS</span>
                Certains de nos serveurs publics sont-ils vulnérables ?
              </div>
<div class="chat-message bot-message">
<span>CYBERBUDDY</span>
                Oui. Trois éléments demandent une action.
                <div class="finding"><b>CRITIQUE</b> Portail d’administration exposé</div>
<div class="finding"><b>ÉLEVÉ</b> Composant web obsolète</div>
<div class="finding"><b>MOYEN</b> Configuration TLS</div>
</div>
<div class="chat-input">Posez votre question à Cywise... <strong>↵</strong></div>
</div>
</div>
</div>
</div>
</section>
<section class="section-pad" id="for-whom">
<div class="container-fluid shell">
<div class="section-heading split-heading">
<div>
<span class="mono">POUR QUI / 05</span>
<h2>CONÇU POUR LES ÉQUIPES QUI VEULENT DE LA CLARTÉ.</h2>
</div>
<p>Choisissez le profil qui correspond à votre organisation et à votre rôle.</p>
</div>
<div class="row g-3 mt-3">
<div class="col-md-6 col-xl-3"><a class="audience-card" href="{{ route('website.audiences.smbs') }}"><span>01</span><h3>PME</h3><p>Protégez l’entreprise sans opérations de sécurité lourdes.</p><b>→</b></a></div>
<div class="col-md-6 col-xl-3"><a class="audience-card" href="{{ route('website.audiences.it-teams') }}"><span>02</span><h3>ÉQUIPES IT</h3><p>Voyez clairement les risques et sachez quoi corriger en premier.</p><b>→</b></a></div>
<div class="col-md-6 col-xl-3"><a class="audience-card" href="{{ route('website.audiences.cisos') }}"><span>03</span><h3>RSSI</h3><p>Centralisez la visibilité et facilitez les décisions de sécurité.</p><b>→</b></a></div>
<div class="col-md-6 col-xl-3"><a class="audience-card" href="{{ route('website.audiences.msps') }}"><span>04</span><h3>MSP</h3><p>Gérez la visibilité sécurité sur plusieurs environnements clients.</p><b>→</b></a></div>
</div>
</div>
</section>
<section class="use-cases" id="use-cases">
<div class="container-fluid shell">
<div class="use-intro">
<span class="mono">CAS D’USAGE / 06</span>
<h2>QU’AVEZ-VOUS BESOIN DE FAIRE ?</h2>
</div>
<div class="case-list">
<a href="{{ route('website.use-cases.find-vulnerabilities') }}"><span>01</span> TROUVER MES VULNÉRABILITÉS <b>↗</b></a>
<a href="{{ route('website.use-cases.monitor-attack-surface') }}"><span>02</span> SURVEILLER MA SURFACE D’ATTAQUE <b>↗</b></a>
<a href="{{ route('website.use-cases.check-leaked-credentials') }}"><span>03</span> VÉRIFIER LES IDENTIFIANTS COMPROMIS <b>↗</b></a>
<a href="{{ route('website.use-cases.create-pssi') }}"><span>04</span> CRÉER MA PSSI <b>↗</b></a>
<a href="{{ route('website.use-cases.prepare-audit') }}"><span>05</span> PRÉPARER UN AUDIT <b>↗</b></a>
<a href="{{ route('website.use-cases.run-pentest') }}"><span>06</span> LANCER UN PENTEST <b>↗</b></a>
</div>
</div>
</section>
<section class="pentest section-pad border-y" id="pentest">
<div class="container-fluid shell">
<div class="row g-5 align-items-center">
<div class="col-lg-7">
<span class="mono">PENTEST / 07</span>
<h2>PARFOIS, IL FAUT UN HUMAIN.</h2>
<p class="lead-copy">Des tests experts pour les applications et infrastructures critiques.</p>
<a class="btn btn-acid btn-lg" href="{{ route('website.solutions.pentest') }}">RÉSERVER UN PENTEST →</a>
</div>
<div class="col-lg-5">
<div class="pentest-sheet">
<div><span>MISSION</span><strong>4 JOURS</strong></div>
<div><span>LIVRABLE</span><strong>1 RAPPORT</strong></div>
<div><span>À PARTIR DE</span><strong>€3,000</strong></div>
<div class="stamp">HUMAIN<br/>VÉRIFIÉ</div>
</div>
</div>
</div>
</div>
</section>
<section class="section-pad" id="resources">
<div class="container-fluid shell">
<div class="section-heading split-heading">
<div>
<span class="mono">RESSOURCES / 08</span>
<h2>DEPUIS LE LAB CYWISE.</h2>
</div>
<a class="btn btn-ghost-brutal" href="{{ route('blog') }}">TOUT VOIR →</a>
</div>
<div class="row g-4 mt-3">
@include('theme::partials.website-v2.posts-loop', ['posts' => $posts, 'locale' => 'fr'])
</div>
</div>
</section>
<section class="pricing border-y" id="pricing">
<div class="container-fluid shell">
<div class="pricing-wrap">
<div>
<span class="mono">TARIFS / 09</span>
<h2>LA SÉCURITÉ SANS LA COMPLEXITÉ DES GRANDS GROUPES.</h2>
</div>
<div class="pricing-card">
<span class="mono">PLATEFORME CYWISE</span>
<h3>COMMENCEZ SIMPLEMENT.</h3>
<p>Surveillez votre entreprise. Identifiez les risques. Agissez.</p>
<a class="btn btn-dark-brutal w-100" href="{{ route('pricing') }}">VOIR LES TARIFS →</a>
</div>
</div>
</div>
</section>
<section class="final-cta" id="cta">
<div class="container-fluid shell text-center">
<span class="mono">PRÊT / 10</span>
<h2>VOUS N’AVEZ PAS BESOIN DE PLUS DE COMPLEXITÉ.</h2>
<h3>VOUS AVEZ BESOIN DE CYWISE.</h3>
<div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
<a class="btn btn-dark-brutal btn-xl" href="{{ route('register') }}">COMMENCER GRATUITEMENT →</a>
<a class="btn btn-white-brutal btn-xl" href="mailto:{{ config('towerify.freshdesk.from_email') }}">DEMANDER UNE DÉMO</a>
</div>
</div>
</section>
</main>
</x-layouts.website-v2>
