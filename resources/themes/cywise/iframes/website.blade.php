<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name') }}</title>

  <!--====== Favicon Icon ======-->
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/ico"/>

  <!-- ===== All CSS files ===== -->
  <link rel="stylesheet" href="{{ asset('cywise/css/website/bootstrap.min.css') }}"/>
  <link rel="stylesheet" href="{{ asset('cywise/css/website/animate.css') }}"/>
  <link rel="stylesheet" href="{{ asset('cywise/css/website/lineicons.css') }}"/>
  <link rel="stylesheet" href="{{ asset('cywise/css/website/ud-styles.css') }}"/>

  <style>
    /* Popup Cybercheck */
    #cybercheck-popup {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .6);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }

    #cybercheck-popup .cybercheck-modal {
      background: #ffffff;
      width: min(900px, 95vw);
      height: min(80vh, 720px);
      border-radius: 8px;
      overflow: hidden;
      position: relative;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .3);
    }

    #cybercheck-popup .cybercheck-close {
      position: absolute;
      top: 8px;
      right: 12px;
      background: rgba(0, 0, 0, 0.05);
      border: 0;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 22px;
      line-height: 1;
      cursor: pointer;
    }

    #cybercheck-popup .cybercheck-close:hover {
      background: rgba(0, 0, 0, 0.1);
    }

    #cybercheck-popup .cybercheck-iframe {
      width: 100%;
      height: 100%;
      border: 0;
    }
  </style>

</head>
<body>

<!-- ====== Hero Start ====== -->
<section class="ud-hero" id="home">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-hero-content wow fadeInUp" data-wow-delay=".2s">
          <h1 class="ud-hero-title">
            La <b>cybersécurité</b> simplifiée
          </h1>
          <p class="ud-hero-desc">
            Protégez vos données et votre activité sans être un expert de la sécurité informatique.
          </p>
          <ul class="ud-hero-buttons">
            <li>
              <a href="{{ route('tools.cybercheck.init') }}" rel="nofollow noopener" target="_blank"
                 class="ud-main-btn ud-white-btn">
                Démarrez l'audit gratuitement
              </a>
            </li>
            <li>
              <a href="{{ route('changelogs') }}" rel="nofollow noopener" target="_blank"
                 class="ud-main-btn ud-link-btn">
                {{ __('What\'s new') }} <i class="lni lni-arrow-right"></i>
              </a>
            </li>
          </ul>
        </div>
        <div class="ud-hero-image wow fadeInUp" data-wow-delay=".25s">
          <img src="{{ asset('cywise/img/screenshot.png') }}" alt="hero-image"/>
          <img
              src="{{ asset('cywise/img/website/hero/dotted-shape.svg') }}"
              alt="shape"
              class="shape shape-1"
          />
          <img
              src="{{ asset('cywise/img/website/hero/dotted-shape.svg') }}"
              alt="shape"
              class="shape shape-2"
          />
        </div>
      </div>
    </div>
  </div>
</section>
<!-- ====== Hero End ====== -->

<!-- ====== Team In Pocket Start ====== -->
<section id="team-pocket" class="ud-about" style="padding: 100px 0; background: #fff;">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 col-md-12">
        <div class="ud-about-content wow fadeInUp" data-wow-delay=".2s">
          <span class="tag">Accompagnement IA</span>
          <h2>Cywise c'est une équipe Cyber dans votre poche !</h2>
          <p>
            Emportez toute votre expertise en cybersécurité partout avec vous. Que vous soyez en déplacement ou au
            bureau, accédez instantanément à vos indicateurs clés, recevez des alertes en temps réel et pilotez votre
            stratégie de défense directement depuis votre smartphone.
          </p>
          <p>
            Notre intégration avec <a href="https://telegram.org" target="_blank">Telegram</a> et <a
                href="https://www.whatsapp.com/" target="_blank">WhatsApp</a> vous permet de rester réactif face aux
            menaces, de consulter vos alertes et de collaborer avec votre équipe en un clin d'oeil. La sécurité de votre
            entreprise ne s'arrête jamais, et votre accès non plus.
          </p>
          <a href="{{ route('register') }}" class="ud-main-btn">Découvrir l'application</a>
        </div>
      </div>
      <div class="col-lg-6 col-md-12">
        <div class="ud-about-image wow fadeInUp" data-wow-delay=".25s" style="display: flex; justify-content: center;">
          <div class="phone-mockup"
               style="position: relative; width: 280px; height: 560px; background: #222; border: 12px solid #333; border-radius: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); overflow: hidden;">
            <!-- Haut du téléphone (encoche/caméra) -->
            <div
                style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 120px; height: 25px; background: #333; border-bottom-left-radius: 15px; border-bottom-right-radius: 15px; z-index: 10;"></div>
            <!-- Écran (zone pour le futur screenshot) -->
            <div class="phone-screen"
                 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #f8f9fa; display: flex; align-items: center; justify-content: center; text-align: center;">
              <img src="{{ asset('cywise/img/screenshot-telegram.png') }}">
            </div>
            <!-- Bouton bas -->
            <div
                style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); width: 40px; height: 4px; background: #333; border-radius: 2px;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- ====== Team In Pocket End ====== -->

<!-- ====== Features Start ====== -->
<section id="features" class="ud-features">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-section-title">
          <span>Fonctionnalités</span>
          <h2>Protégez ce qui est accessible sur internet</h2>
          <p>
            3 technologies complémentaires pour une protection renforcée !
          </p>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xl-4 col-lg-4 col-sm-6">
        <div class="ud-single-feature wow fadeInUp" data-wow-delay=".1s">
          <div class="ud-feature-icon">
            <i class="lni lni-search"></i>
          </div>
          <div class="ud-feature-content">
            <h3 class="ud-feature-title">Scanner de vulnérabilités</h3>
            <p class="ud-feature-desc">
              Surveillance proactive et correction automatisée. Analyse continue de votre infrastructure pour détecter
              plus de 50 000 vulnérabilités.
            </p>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-4 col-sm-6">
        <div class="ud-single-feature wow fadeInUp" data-wow-delay=".15s">
          <div class="ud-feature-icon">
            <i class="lni lni-shield"></i>
          </div>
          <div class="ud-feature-content">
            <h3 class="ud-feature-title">Fuites de données</h3>
            <p class="ud-feature-desc">
              Analyse quotidienne de 10 millions d'identifiants fuités. Détectez les comptes compromis avant toute
              exploitation malveillante.
            </p>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-4 col-sm-6">
        <div class="ud-single-feature wow fadeInUp" data-wow-delay=".2s">
          <div class="ud-feature-icon">
            <i class="lni lni-target"></i>
          </div>
          <div class="ud-feature-content">
            <h3 class="ud-feature-title">Honeypots intelligents</h3>
            <p class="ud-feature-desc">
              Piégez les attaquants avec des leurres numériques. Identifiez les menaces en temps réel avant qu'elles
              ne touchent vos systèmes critiques.
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="row mt-5">
      <div class="col-lg-12">
        <div class="ud-section-title">
          <h2>Protégez vos actifs internes</h2>
          <p>
            3 outils pour anticiper les problèmes !
          </p>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xl-4 col-lg-4 col-sm-6">
        <div class="ud-single-feature wow fadeInUp" data-wow-delay=".1s">
          <div class="ud-feature-icon">
            <i class="lni lni-cog"></i>
          </div>
          <div class="ud-feature-content">
            <h3 class="ud-feature-title">Hardening</h3>
            <p class="ud-feature-desc">
              Renforcez la sécurité de vos serveurs Linux et Windows. Détectez rapidement tout écart de configuration de
              vos machines.
            </p>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-4 col-sm-6">
        <div class="ud-single-feature wow fadeInUp" data-wow-delay=".15s">
          <div class="ud-feature-icon">
            <i class="lni lni-display"></i>
          </div>
          <div class="ud-feature-content">
            <h3 class="ud-feature-title">Agents & Détection</h3>
            <p class="ud-feature-desc">
              Surveillez les activités suspectes en temps réel. Détection proactive des comportements anormaux sur vos
              serveurs.
            </p>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-4 col-sm-6">
        <div class="ud-single-feature wow fadeInUp" data-wow-delay=".2s">
          <div class="ud-feature-icon">
            <i class="lni lni-stats-up"></i>
          </div>
          <div class="ud-feature-content">
            <h3 class="ud-feature-title">Métriques & Disponibilité</h3>
            <p class="ud-feature-desc">
              Collectez les métriques essentielles (CPU, stockage) pour garantir la stabilité et la performance de
              votre infrastructure.
            </p>
          </div>
        </div>
      </div>
      <div class="row mt-5">
        <div class="col-lg-12">
          <div class="ud-section-title">
            <h2>Pilotez votre sécurité en continu</h2>
            <p>
              3 outils pour agir vite et efficacement !
            </p>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-xl-4 col-lg-4 col-sm-6">
          <div class="ud-single-feature wow fadeInUp" data-wow-delay=".1s">
            <div class="ud-feature-icon">
              <i class="lni lni-bullhorn"></i>
            </div>
            <div class="ud-feature-content">
              <h3 class="ud-feature-title">Bulletin d'information</h3>
              <p class="ud-feature-desc">
                Recevez un rapport hebdomadaire clair et priorisé directement dans votre boîte mail ou intégrez un flux
                JSON à vos outils de sécurité (SIEM, SOC, etc.). Les vulnérabilités critiques sont mises en avant pour
                une action immédiate.
              </p>
            </div>
          </div>
        </div>
        <div class="col-xl-4 col-lg-4 col-sm-6">
          <div class="ud-single-feature wow fadeInUp" data-wow-delay=".15s">
            <div class="ud-feature-icon">
              <i class="lni lni-list"></i>
            </div>
            <div class="ud-feature-content">
              <h3 class="ud-feature-title">CyberTodo</h3>
              <p class="ud-feature-desc">
                Créez des accès dédiés et sécurisés pour chaque prestataire, limités à leur périmètre. Supervisez
                l'ensemble des vulnérabilités quels que soient les acteurs impliqués via une seule interface
                centralisée.
              </p>
            </div>
          </div>
        </div>
        <div class="col-xl-4 col-lg-4 col-sm-6">
          <div class="ud-single-feature wow fadeInUp" data-wow-delay=".15s">
            <div class="ud-feature-icon">
              <i class="lni lni-code"></i>
            </div>
            <div class="ud-feature-content">
              <h3 class="ud-feature-title">Scripts de remédiation</h3>
              <p class="ud-feature-desc">
                Nous générons des scripts Bash (Linux) ou PowerShell (Windows) prêts à l'emploi, pour corriger vos
                vulnérabilités sans intervention manuelle. Gain de temps, réduction des erreurs.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>
<!-- ====== Features End ====== -->

<section id="ai-assistant" class="ud-about">
  <div class="container">
    <div class="ud-about-wrapper wow fadeInUp" data-wow-delay=".2s">
      <div class="ud-about-content-wrapper">
        <div class="ud-about-content">
          <span class="tag">Accompagnement IA</span>
          <h2>CyberBuddy & CyberScribe</h2>
          <p>
            Votre expert en cybersécurité, disponible 24/7. CyberBuddy vous guide en temps réel en s'appuyant sur des
            bases de connaissances vérifiées (NIST, ANSSI, etc.)
          </p>
          <p>
            CyberScribe vous accompagne pas à pas pour rédiger des documents clairs et conformes (Charte Informatique,
            Charte IA, Politique de Sécurité des Systèmes d'Information) grâce à l'intelligence artificielle.
          </p>
          <a href="{{ route('register') }}" class="ud-main-btn">Essayer gratuitement</a>
        </div>
      </div>
      <div class="ud-about-image">
        <img src="{{ asset('cywise/img/website/about/about-image.svg') }}" alt="about-image"/>
      </div>
    </div>
  </div>
</section>
<!-- ====== About End ====== -->

<!-- ====== Pricing Start ====== -->
@php
$essentiel = \Wave\Plan::where('name', 'Essentiel')->where('active', 1)->firstOrFail();
$standard = \Wave\Plan::where('name', 'Standard')->where('active', 1)->firstOrFail();
$premium = \Wave\Plan::where('name', 'Premium')->where('active', 1)->firstOrFail();
$essentielFeatures = explode(',', $essentiel->features);
$standardFeatures = explode(',', $standard->features);
$premiumFeatures = explode(',', $premium->features);
@endphp
<section id="pricing" class="ud-pricing">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-section-title mx-auto text-center">
          <span>Tarifs</span>
          <h2>Nos offres</h2>
          <p>
            Des solutions adaptées à chaque besoin.
          </p>
        </div>
      </div>
    </div>
    <div class="ud-pricing-toggle text-center mb-4">
      <div class="btn-group" role="group" aria-label="Choix de facturation">
        <button type="button" id="billing-monthly" class="ud-main-btn ud-white-btn" style="margin-right:8px;">
          Mensuel
        </button>
        <button type="button" id="billing-yearly" class="ud-main-btn ud-border-btn">
          Annuel
        </button>
      </div>
    </div>
    <div class="row g-0 align-items-center justify-content-center">
      <div class="col-lg-4 col-md-6 col-sm-10">
        <div class="ud-single-pricing first-item wow fadeInUp" data-wow-delay=".15s">
          <div class="ud-pricing-header">
            <h3>{{ \Str::upper($essentiel->name) }}</h3>
            <h4>
              <span class="price-amount" data-month="{{ $essentiel->monthly_price }}"
                    data-year="{{ $essentiel->yearly_price }}">
                {{ $essentiel->monthly_price }}
              </span> {{ $essentiel->currency }} <span class="price-period"></span>
              <p class="price-equivalent text-muted small"
                 style="font-size: 0.8rem; margin-top: 5px; min-height: 1.2rem;"></p>
            </h4>
          </div>
          <div class="ud-pricing-body">
            <ul>
              @foreach($essentielFeatures as $feature)
              <li>{!! $feature !!}</li>
              @endforeach
            </ul>
          </div>
          <div class="ud-pricing-footer">
            <a href="/settings/subscription" class="ud-main-btn ud-border-btn">
              {{ __('Subscribe >') }}
            </a>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 col-sm-10">
        <div class="ud-single-pricing active wow fadeInUp" data-wow-delay=".1s">
          <span class="ud-popular-tag">POPULAIRE</span>
          <div class="ud-pricing-header">
            <h3>{{ \Str::upper($standard->name) }}</h3>
            <h4>
              <span class="price-amount" data-month="{{ $standard->monthly_price }}"
                    data-year="{{ $standard->yearly_price }}">
                {{ $standard->monthly_price }}
              </span> {{ $standard->currency }} <span class="price-period"></span>
              <p class="price-equivalent text-muted small"
                 style="font-size: 0.8rem; margin-top: 5px; min-height: 1.2rem; color: white !important;"></p>
            </h4>
          </div>
          <div class="ud-pricing-body">
            <ul>
              @foreach($standardFeatures as $feature)
              <li>{!! $feature !!}</li>
              @endforeach
            </ul>
          </div>
          <div class="ud-pricing-footer">
            <a href="/settings/subscription" class="ud-main-btn ud-white-btn">
              {{ __('Subscribe >') }}
            </a>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 col-sm-10">
        <div class="ud-single-pricing last-item wow fadeInUp" data-wow-delay=".15s">
          <div class="ud-pricing-header">
            <h3>{{ \Str::upper($premium->name) }}</h3>
            <h4>
              <span class="price-amount" data-month="{{ $premium->monthly_price }}"
                    data-year="{{ $premium->yearly_price }}">
                {{ $premium->monthly_price }}
              </span> {{ $premium->currency }} <span class="price-period"></span>
              <p class="price-equivalent text-muted small"
                 style="font-size: 0.8rem; margin-top: 5px; min-height: 1.2rem;"></p>
            </h4>
          </div>
          <div class="ud-pricing-body">
            <ul>
              @foreach($premiumFeatures as $feature)
              <li>{!! $feature !!}</li>
              @endforeach
            </ul>
          </div>
          <div class="ud-pricing-footer">
            <a href="/settings/subscription" class="ud-main-btn ud-border-btn">
              {{ __('Subscribe >') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- ====== Pricing End ====== -->

<!-- ====== Media Start ====== -->
<section id="podcasts" class="ud-blog-grids" style="padding: 80px 0; background: #f4f7fa;">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-section-title mx-auto text-center">
          <span>Média</span>
          <h2>Webinaires & Shorts</h2>
          <p>
            Retrouvez nos webinaires et vidéos courtes.
          </p>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-4 col-md-6">
        <div class="ud-single-blog wow fadeInUp" data-wow-delay=".1s">
          <div class="ud-blog-image">
            <div class="ratio ratio-16x9">
              <iframe src="https://www.youtube.com/embed/TdxKrUFlO-Y" title="YouTube video player" frameborder="0"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                      allowfullscreen></iframe>
            </div>
          </div>
          <div class="ud-blog-content">
            <span class="ud-blog-date">Short - 1 Février 2026</span>
            <h3 class="ud-blog-title">
              <a href="https://youtu.be/TdxKrUFlO-Y">
                Comment protéger mon site web ?
              </a>
            </h3>
            <p class="ud-blog-desc">
              Question du jour : Mon site résiste-t-il aux attaques courantes ?
            </p>
            <br>
            <p class="ud-blog-desc">
              Beaucoup n'en ont aucune certitude. Cywise cartographie votre surface exposée et identifie immédiatement
              les failles prioritaires.
            </p>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="ud-single-blog wow fadeInUp" data-wow-delay=".15s">
          <div class="ud-blog-image">
            <div class="ratio ratio-16x9">
              <iframe src="https://www.youtube.com/embed/N9KSao79nTg" title="YouTube video player" frameborder="0"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                      allowfullscreen></iframe>
            </div>
          </div>
          <div class="ud-blog-content">
            <span class="ud-blog-date">Webinaire - 12 Décembre 2025</span>
            <h3 class="ud-blog-title">
              <a href="https://youtu.be/N9KSao79nTg">
                Les fuites de mots de passes dont vous n'avez jamais entendu parler
              </a>
            </h3>
            <p class="ud-blog-desc">
              Vous pensez que vos mots de passe sont sécurisés ?
            </p>
            <br>
            <p class="ud-blog-desc">
              Détrompez-vous. Chaque jour, des milliers de combinaisons d’identifiants et de mots de passe fuitent sur
              le web, souvent sans que vous ne vous en rendiez compte. Ces données, récupérées lors de fuites massives
              ou de cyberattaques ciblées, deviennent des clefs universelles pour les pirates. Ils les utilisent pour
              s'infiltrer dans vos comptes professionnels, personnels, et même dans les systèmes de votre entreprise.
            </p>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="ud-single-blog wow fadeInUp" data-wow-delay=".2s">
          <div class="ud-blog-image">
            <div class="ratio ratio-16x9">
              <iframe src="https://www.youtube.com/embed/l6NYLhtPKhE" title="YouTube video player" frameborder="0"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                      allowfullscreen></iframe>
            </div>
          </div>
          <div class="ud-blog-content">
            <span class="ud-blog-date">Webinaire - 14 Novembre 2025</span>
            <h3 class="ud-blog-title">
              <a href="https://youtu.be/l6NYLhtPKhE">
                Pourquoi les cybercriminels vous ciblent ?
              </a>
            </h3>
            <p class="ud-blog-desc">
              La cybercriminalité n'est plus une menace réservée aux grandes entreprises.
            </p>
            <br>
            <p>
              Beaucoup de dirigeants se rassurent encore : « Nous sommes trop petits, personne ne nous piratera. »
              Pourtant, la réalité est tout autre : les PME sont aujourd'hui la cible privilégiée des attaques
              opportunistes et automatisées. Les pirates ne vous visent pas personnellement, mais ils scannent Internet
              à la recherche de failles courantes, de mots de passe réutilisés ou de systèmes non mis à jour. Dès qu'une
              vulnérabilité est détectée, vous entrez dans leur ligne de mire (sans même qu’ils sachent qui vous êtes).
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- ====== Media End ====== -->

<!-- ====== FAQ Start ====== -->
<section id="faq" class="ud-faq">
  <div class="shape">
    <img src="{{ asset('cywise/img/website/faq/shape.svg') }}" alt=""/>
  </div>
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-section-title text-center mx-auto">
          <span>FAQ</span>
          <h2>Questions fréquentes</h2>
          <p>
            Nos réponses aux questions les plus courantes sur Cywise.
          </p>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-6">
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>Quels sont les modes d'hébergement disponibles ?</span>
            </button>
            <div id="collapseThree" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                Cywise est disponible en version <a href="{{ route('register') }}" target="_blank">SaaS</a> ou <a
                    href="https://github.com/computablefacts/cywise" target="_blank">auto-hébergée</a>.
              </div>
            </div>
          </div>
        </div>
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>Puis-je scanner mon réseau interne avec Cywise ?</span>
            </button>
            <div id="collapseTwo" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                Il n'est pas possible de scanner votre réseau interne avec la version <a href="{{ route('register') }}"
                                                                                         target="_blank">SaaS</a> de
                Cywise. Cependant, la version <a href="https://github.com/computablefacts/cywise" target="_blank">auto-hébergée</a>
                de Cywise vous permet de le faire !
              </div>
            </div>
          </div>
        </div>
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>J'ai déjà une une PSSI, puis-je l'importer dans Cywise ?</span>
            </button>
            <div id="collapseOne" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                Oui, Cywise vous permet d'intégrer vos propres documents (Charte Informatique, Charte IA, PSSI, etc.) à
                CyberBuddy. Vos équipes peuvent alors poser des questions en langage naturel et obtenir des réponses
                extraites directement de vos ressources internes.
              </div>
            </div>
          </div>
        </div>
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>Comment fonctionne la détection d'activités suspectes ?</span>
            </button>
            <div id="collapseFive" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                Nous utilisons <a href="https://osquery.io/" target="_blank">Osquery</a> pour collecter les événements
                de sécurité de vos machines en temps réel. Des règles écrites par nos experts analysent ces
                comportements pour identifier toute menace dès son apparition.
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>Qu'est-ce que le "Hardening" ?</span>
            </button>
            <div id="collapseFour" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                Le hardening (ou durcissement) consiste à optimiser la configuration de vos serveurs pour réduire leur
                surface d'attaque. Cywise utilise des règles <a href="https://www.ossec.net/" target="_blank">OSSEC</a>
                écrites par nos experts pour auditer et renforcer vos machines.
              </div>
            </div>
          </div>
        </div>
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseSix">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>Qu'est-ce qu'un "Honeypot" ?</span>
            </button>
            <div id="collapseSix" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                Un honeypot est un leurre conçu pour attirer les cyberattaquants et analyser leurs méthodes. Nos
                honeypots permettent d'identifier en temps réel les attaques en cours, de vérifier si votre entreprise
                est ciblée et d'évaluer les risques pour votre infrastructure, sans l'exposer directement.
              </div>
            </div>
          </div>
        </div>
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseSeven">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>Comment les fuites de données révèlent-elles des adresses e-mails internes compromises ?</span>
            </button>
            <div id="collapseSeven" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                Les fuites révèlent si des identifiants professionnels de votre organisation circulent dans des bases
                piratées. Cela permet d'anticiper les risques (phishing, usurpation) et de sécuriser les comptes
                concernés.
              </div>
            </div>
          </div>
        </div>
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseHeight">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>En quoi les fuites de données aident-elles à détecter les comptes utilisant des mots de passe compromis ?</span>
            </button>
            <div id="collapseHeight" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                La plupart de nos fuites de données contiennent des triplets (identifiant, mot de passe, site web
                d'utilisation). En croisant les sites web concernés avec vos propres domaines, nous vous permettons
                d'identifier rapidement les connexions suspectes à votre infrastructure. Cette analyse proactive vous
                permet de bloquer les comptes compromis avant toute exploitation malveillante, d'imposer une
                réinitialisation des mots de passe aux utilisateurs exposés et de renforcer la surveillance des comptes
                les plus vulnérables.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- ====== FAQ End ====== -->

<!-- ====== Testimonials Start ====== -->
<section id="testimonials" class="ud-testimonials">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-section-title mx-auto text-center">
          <span>Témoignages</span>
          <h2>Ce qu'ils en disent</h2>
          <p>
            Nos clients partagent leur expérience.
          </p>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-4 col-md-6">
        <div class="ud-single-testimonial wow fadeInUp" data-wow-delay=".2s">
          <div class="ud-testimonial-ratings">
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
          </div>
          <div class="ud-testimonial-content">
            <p>
              "Cywise est le meilleur produit que j'ai vu en matière de détection de vulnérabilités de qualité, sans
              faux positifs."
            </p>
          </div>
          <div class="ud-testimonial-info">
            <div class="ud-testimonial-image">
              <img src="{{ asset('cywise/img/logo-cac40.png') }}" alt="CAC40"/>
            </div>
            <div class="ud-testimonial-meta">
              <h4>Julien B.</h4>
              <p>CISO d'un groupe du CAC40</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="ud-single-testimonial wow fadeInUp" data-wow-delay=".1s">
          <div class="ud-testimonial-ratings">
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
          </div>
          <div class="ud-testimonial-content">
            <p>
              "Avec Cywise, nous avons vu produire une PSSI claire et complète pour notre client en quelques jours au
              lieu de plusieurs semaines. Un vrai game changer aussi bien en terme de temps qu'en terme de qualité et de
              suivi."
            </p>
          </div>
          <div class="ud-testimonial-info">
            <div class="ud-testimonial-image">
              <img src="{{ asset('cywise/img/logo-ackero.jpg') }}" alt="Ackero"/>
            </div>
            <div class="ud-testimonial-meta">
              <h4>Augustin B.</h4>
              <p>Co-fondateur d'Ackero </p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="ud-single-testimonial wow fadeInUp" data-wow-delay=".15s">
          <div class="ud-testimonial-ratings">
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
            <i class="lni lni-star-filled"></i>
          </div>
          <div class="ud-testimonial-content">
            <p>
              "La solution a amélioré notre visibilité des périmètres exposés et internes. Nous avons été notifiés
              automatiquement des vulnérabilités à corriger. Rien de critique, heureusement. Depuis, nous sommes alertés
              dès qu'un changement important est détecté. En somme l'idéal pour une PME comme la nôtre."
            </p>
          </div>
          <div class="ud-testimonial-info">
            <div class="ud-testimonial-image">
              <img src="{{ asset('cywise/img/logo-oppscience.png') }}" alt="Oppscience"/>
            </div>
            <div class="ud-testimonial-meta">
              <h4>Sylvain M.</h4>
              <p>RSSI d'Oppscience</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-brands wow fadeInUp" data-wow-delay=".2s">
          <div class="ud-title">
            <h6>Utilisé et approuvé par</h6>
          </div>
          <div class="ud-brands-logo">
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/logo-ista.png') }}" alt="ISTA"/>
            </div>
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/logo-hdwsec.jpeg') }}" alt="HDW Sec"/>
            </div>
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/logo-elephantastic.png') }}" alt="Elephantastic"/>
            </div>
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/logo-mindtechnologies.png') }}" alt="Netemedia"/>
            </div>
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/logo-netemedia.png') }}" alt="Netemedia"/>
            </div>
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/logo-idcapt.png') }}" alt="ID.capt"/>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- ====== Testimonials End ====== -->

<!-- ====== Contact Start ====== -->
<section id="contact" class="ud-contact">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-xl-8 col-lg-7">
        <div class="ud-contact-content-wrapper">
          <div class="ud-contact-title">
            <span style="text-transform: uppercase">Échangeons ensemble</span>
            <h2>
              N'hésitez pas à nous partager<br>vos problématiques cyber !
            </h2>
          </div>
          <div class="ud-contact-info-wrapper">
            <div class="ud-single-info">
              <div class="ud-info-icon">
                <i class="lni lni-map-marker"></i>
              </div>
              <div class="ud-info-meta">
                <h5>Notre adresse</h5>
                <p>178 boulevard Haussmann<br>75008 Paris<br>France</p>
              </div>
            </div>
            <div class="ud-single-info">
              <div class="ud-info-icon">
                <i class="lni lni-envelope"></i>
              </div>
              <div class="ud-info-meta">
                <h5>Besoin d'aide ?</h5>
                <p>Utilisez le formulaire ci-contre pour nous contacter !</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-5">
        <div class="ud-contact-form-wrapper wow fadeInUp" data-wow-delay=".2s">
          <h3 class="ud-contact-form-title">Envoyez-nous un message</h3>
          <form class="ud-contact-form" id="ud-contact-form">
            <div class="ud-form-group">
              <label for="fullName">Nom*</label>
              <input type="text" name="fullName" placeholder="John Doe"/>
            </div>
            <div class="ud-form-group">
              <label for="email">Email*</label>
              <input type="email" name="email" placeholder="example@yourmail.com"/>
            </div>
            <div class="ud-form-group">
              <label for="phone">Téléphone*</label>
              <input type="text" name="phone" placeholder="+33 x xx xx xx xx"/>
            </div>
            <div class="ud-form-group">
              <label for="message">Message*</label>
              <textarea name="message" rows="1" placeholder="saisissez votre message ici"></textarea>
            </div>
            <div class="ud-form-group mb-0">
              <button type="submit" class="ud-main-btn">
                {{ __('Send') }}
              </button>
              <div id="contact-status" class="small mt-2"></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- ====== Contact End ====== -->

<!-- ====== Back To Top Start ====== -->
<a href="javascript:void(0)" class="back-to-top">
  <i class="lni lni-chevron-up"> </i>
</a>
<!-- ====== Back To Top End ====== -->

<!-- ====== CyberCheck Popup Start ====== -->
<div id="cybercheck-popup" aria-hidden="true" role="dialog" aria-label="Cybercheck">
  <div class="cybercheck-modal">
    <button id="cybercheck-close" class="cybercheck-close" aria-label="Fermer la fenêtre">×</button>
    <iframe class="cybercheck-iframe" src="" data-src="{{ route('tools.cybercheck.init') }}"
            title="Cybercheck"></iframe>
  </div>
</div>
<!-- ====== CyberCheck Popup End ====== -->

<!-- ====== All Javascript Files ====== -->
<script src="{{ asset('cywise/js/website/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('cywise/js/website/wow.min.js') }}"></script>
<script src="{{ asset('cywise/js/website/main.js') }}"></script>
<script>

  // ==== for pricing
  document.addEventListener('DOMContentLoaded', function () {

    const monthlyBtn = document.getElementById('billing-monthly');
    const yearlyBtn = document.getElementById('billing-yearly');

    function setBilling(mode) {
      document.querySelectorAll('#pricing .ud-single-pricing').forEach(function (card) {

        const amountEl = card.querySelector('.price-amount');
        const periodEl = card.querySelector('.price-period');
        const equivalentEl = card.querySelector('.price-equivalent');
        let m = parseFloat(amountEl.getAttribute('data-month'));
        let y = parseFloat(amountEl.getAttribute('data-year'));

        if (!m) {
          m = 0;
        }
        if (!y) {
          y = 0;
        }
        if (mode === 'yearly') {
          amountEl.textContent = y;
          periodEl.textContent = ' / an';
          if (y > 0) {
            const monthlyEquiv = (y / 12).toFixed(0);
            equivalentEl.textContent = `soit ${monthlyEquiv} € / mois`;
          } else {
            equivalentEl.textContent = '';
          }
        } else {
          amountEl.textContent = m;
          periodEl.textContent = ' / mois';
          if (m > 0) {
            const yearlyEquiv = (m * 12).toFixed(0);
            equivalentEl.textContent = `soit ${yearlyEquiv} € / an`;
          } else {
            equivalentEl.textContent = '';
          }
        }
      });
      if (mode === 'monthly') {
        monthlyBtn.classList.add('ud-white-btn');
        monthlyBtn.classList.remove('ud-border-btn');
        yearlyBtn.classList.add('ud-border-btn');
        yearlyBtn.classList.remove('ud-white-btn');
      } else {
        yearlyBtn.classList.add('ud-white-btn');
        yearlyBtn.classList.remove('ud-border-btn');
        monthlyBtn.classList.add('ud-border-btn');
        monthlyBtn.classList.remove('ud-white-btn');
      }
    }

    if (monthlyBtn && yearlyBtn) {
      monthlyBtn.addEventListener('click', function () {
        setBilling('monthly');
      });
      yearlyBtn.addEventListener('click', function () {
        setBilling('yearly');
      });
      setBilling('monthly');
    }
  });

  // ==== for menu scroll
  const pageLink = document.querySelectorAll(".ud-menu-scroll");

  pageLink.forEach((elem) => {
    elem.addEventListener("click", (e) => {
      e.preventDefault();
      document.querySelector(elem.getAttribute("href")).scrollIntoView({
        behavior: "smooth",
        offsetTop: 1 - 60,
      });
    });
  });

  // section menu active
  function onScroll(event) {

    const sections = document.querySelectorAll(".ud-menu-scroll");
    const scrollPos =
      window.pageYOffset ||
      document.documentElement.scrollTop ||
      document.body.scrollTop;

    for (let i = 0; i < sections.length; i++) {
      const currLink = sections[i];
      const val = currLink.getAttribute("href");
      const refElement = document.querySelector(val);
      const scrollTopMinus = scrollPos + 73;
      if (
        refElement.offsetTop <= scrollTopMinus &&
        refElement.offsetTop + refElement.offsetHeight > scrollTopMinus
      ) {
        document
          .querySelector(".ud-menu-scroll")
          .classList.remove("active");
        currLink.classList.add("active");
      } else {
        currLink.classList.remove("active");
      }
    }
  }

  window.document.addEventListener("scroll", onScroll);

  // ==== contact form submit
  document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('ud-contact-form');
    const status = document.getElementById('contact-status');

    if (!form) {
      return;
    }

    function setStatus(text, ok) {
      if (!status) {
        return;
      }
      status.textContent = text || '';
      status.style.color = ok ? '#0a7e07' : '#b00020';
    }

    form.addEventListener('submit', async function (e) {

      e.preventDefault();
      setStatus('Envoi en cours...', true);

      const payload = {
        fullName: form.querySelector('input[name="fullName"]').value.trim(),
        email: form.querySelector('input[name="email"]').value.trim(),
        phone: form.querySelector('input[name="phone"]').value.trim(),
        message: form.querySelector('textarea[name="message"]').value.trim(),
      };
      try {
        const resp = await fetch('/api/contact', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(payload),
        });
        const data = await resp.json().catch(() => ({}));
        if (!resp.ok || data.ok === false) {
          throw new Error(data.message || 'Une erreur est survenue.');
        }
        setStatus(data.message || 'Message envoyé, merci !', true);
        form.reset();
      } catch (err) {
        setStatus(err.message || 'Impossible d\'envoyer le message pour le moment.', false);
      }
    });
  });

  // ==== Affiche la popup après 15 secondes
  document.addEventListener('DOMContentLoaded', function () {

    let popup = document.getElementById('cybercheck-popup');

    if (!popup) {
      return;
    }

    // Ne pas afficher la popup sur mobile
    const isMobile = (
      (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) ||
      /Mobi|Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(navigator.userAgent)
    );
    if (isMobile) {
      return;
    }

    let iframe = popup.querySelector('.cybercheck-iframe');
    let closeBtn = document.getElementById('cybercheck-close');

    function openPopup() {
      if (iframe && iframe.dataset.src) {
        iframe.src = iframe.dataset.src;
      }
      popup.style.display = 'flex';
    }

    function closePopup() {
      popup.style.display = 'none';
      if (iframe) {
        iframe.src = '';
      }
    }

    // Affichage différé après 15 secondes
    setTimeout(openPopup, 15000);

    // Fermeture via bouton "X"
    if (closeBtn) {
      closeBtn.addEventListener('click', closePopup);
    }

    // Fermeture en cliquant sur l'overlay
    popup.addEventListener('click', function (e) {
      if (e.target === popup) {
        closePopup();
      }
    });
  });
</script>
</body>
</html>
