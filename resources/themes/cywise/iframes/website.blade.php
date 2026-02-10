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

<!-- ====== Features Start ====== -->
<section id="features" class="ud-features">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-section-title">
          <span>Fonctionnalités</span>
          <h2>Protégez ce qui est accessible sur internet</h2>
          <p>
            Cywise intègre toutes les fonctionnalités essentielles aux TPE et PME pour sécuriser leur infrastructure
            exposée.
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
              Renforcez la sécurité de vos serveurs Linux et Windows grâce à un audit complet et l'application de
              référentiels reconnus.
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
            bases de connaissances vérifiées.
          </p>
          <p>
            CyberScribe vous accompagne pas à pas pour rédiger des documents clairs et conformes (Charte Informatique,
            PSSI) grâce à l'intelligence artificielle.
          </p>
          <p class="text-xs text-gray-600 mt-2">Note: en auto‑hébergé, fournissez votre propre clé d'API pour activer
            ces fonctionnalités.</p>
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
<section id="pricing" class="ud-pricing">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-section-title mx-auto text-center">
          <span>Pricing</span>
          <h2>Our Pricing Plans</h2>
          <p>
            There are many variations of passages of Lorem Ipsum available
            but the majority have suffered alteration in some form.
          </p>
        </div>
      </div>
    </div>
    <div class="row g-0 align-items-center justify-content-center">
      <div class="col-lg-4 col-md-6 col-sm-10">
        <div class="ud-single-pricing first-item wow fadeInUp" data-wow-delay=".15s">
          <div class="ud-pricing-header">
            <h3>STARTING FROM</h3>
            <h4>$ 19.99/mo</h4>
          </div>
          <div class="ud-pricing-body">
            <ul>
              <li>5 User</li>
              <li>All UI components</li>
              <li>Lifetime access</li>
              <li>Free updates</li>
              <li>Use on 1 (one) project</li>
              <li>4 Months support</li>
            </ul>
          </div>
          <div class="ud-pricing-footer">
            <a href="javascript:void(0)" class="ud-main-btn ud-border-btn">
              Purchase Now
            </a>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 col-sm-10">
        <div class="ud-single-pricing active wow fadeInUp" data-wow-delay=".1s">
          <span class="ud-popular-tag">POPULAR</span>
          <div class="ud-pricing-header">
            <h3>STARTING FROM</h3>
            <h4>$ 30.99/mo</h4>
          </div>
          <div class="ud-pricing-body">
            <ul>
              <li>5 User</li>
              <li>All UI components</li>
              <li>Lifetime access</li>
              <li>Free updates</li>
              <li>Use on 1 (one) project</li>
              <li>4 Months support</li>
            </ul>
          </div>
          <div class="ud-pricing-footer">
            <a href="javascript:void(0)" class="ud-main-btn ud-white-btn">
              Purchase Now
            </a>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 col-sm-10">
        <div class="ud-single-pricing last-item wow fadeInUp" data-wow-delay=".15s">
          <div class="ud-pricing-header">
            <h3>STARTING FROM</h3>
            <h4>$ 70.99/mo</h4>
          </div>
          <div class="ud-pricing-body">
            <ul>
              <li>5 User</li>
              <li>All UI components</li>
              <li>Lifetime access</li>
              <li>Free updates</li>
              <li>Use on 1 (one) project</li>
              <li>4 Months support</li>
            </ul>
          </div>
          <div class="ud-pricing-footer">
            <a href="javascript:void(0)" class="ud-main-btn ud-border-btn">
              Purchase Now
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- ====== Pricing End ====== -->

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
            Retrouvez les réponses aux questions les plus courantes sur Cywise.
          </p>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-6">
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".1s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>Qu'est-ce que le Fond Documentaire ?</span>
            </button>
            <div id="collapseOne" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                Il permet d'intégrer vos propres documents (Charte Informatique, PSSI) à CyberBuddy. Vos équipes
                peuvent alors poser des questions en langage naturel et obtenir des réponses extraites directement de
                vos ressources internes.
              </div>
            </div>
          </div>
        </div>
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".15s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>Cywise supporte-t-il le SSO ?</span>
            </button>
            <div id="collapseTwo" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                Oui, Cywise intègre un module SSO moderne compatible avec les standards du marché (OAuth 2.0, SAML,
                OpenID Connect) pour un contrôle des accès unifié.
              </div>
            </div>
          </div>
        </div>
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
                La plupart de nos modules sont disponibles en version auto-hébergée (SaaS) ou installables sur votre
                propre infrastructure, vous offrant un contrôle total sur vos données de sécurité.
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".1s">
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
                surface d'attaque. Cywise utilise des règles OSSEC pour auditer et renforcer vos machines.
              </div>
            </div>
          </div>
        </div>
        <div class="ud-single-faq wow fadeInUp" data-wow-delay=".15s">
          <div class="accordion">
            <button class="ud-faq-btn collapsed" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
              <span>Comment fonctionne la détection d'activités suspectes ?</span>
            </button>
            <div id="collapseFive" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                Nous utilisons des agents basés sur Osquery pour collecter les événements de sécurité en temps réel.
                Des règles expertes analysent ces comportements pour identifier toute menace dès son apparition.
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
              <span>CyberBuddy est-il disponible hors ligne ?</span>
            </button>
            <div id="collapseSix" class="accordion-collapse collapse">
              <div class="ud-faq-body">
                CyberBuddy nécessite une connexion internet. Dans la version auto-hébergée, vous pouvez configurer
                votre propre clé d'API pour activer les fonctionnalités d'intelligence artificielle.
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
          <span>Testimonials</span>
          <h2>What our Customers Says</h2>
          <p>
            There are many variations of passages of Lorem Ipsum available
            but the majority have suffered alteration in some form.
          </p>
        </div>
      </div>
    </div>
    <div class="row">
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
              “Our members are so impressed. It's intuitive. It's clean.
              It's distraction free. If you're building a community.
            </p>
          </div>
          <div class="ud-testimonial-info">
            <div class="ud-testimonial-image">
              <img src="{{ asset('cywise/img/website/testimonials/author-01.png') }}" alt="author"/>
            </div>
            <div class="ud-testimonial-meta">
              <h4>Sabo Masties</h4>
              <p>Founder @UIdeck</p>
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
              “Our members are so impressed. It's intuitive. It's clean.
              It's distraction free. If you're building a community.
            </p>
          </div>
          <div class="ud-testimonial-info">
            <div class="ud-testimonial-image">
              <img src="{{ asset('cywise/img/website/testimonials/author-02.png') }}" alt="author"/>
            </div>
            <div class="ud-testimonial-meta">
              <h4>Margin Gesmu</h4>
              <p>Founder @Lineicons</p>
            </div>
          </div>
        </div>
      </div>
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
              “Our members are so impressed. It's intuitive. It's clean.
              It's distraction free. If you're building a community.
            </p>
          </div>
          <div class="ud-testimonial-info">
            <div class="ud-testimonial-image">
              <img src="{{ asset('cywise/img/website/testimonials/author-03.png') }}" alt="author"/>
            </div>
            <div class="ud-testimonial-meta">
              <h4>William Smith</h4>
              <p>Founder @GrayGrids</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-brands wow fadeInUp" data-wow-delay=".2s">
          <div class="ud-title">
            <h6>Trusted and Used by</h6>
          </div>
          <div class="ud-brands-logo">
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/website/brands/ayroui.svg') }}" alt=""/>
            </div>
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/website/brands/uideck.svg') }}" alt=""/>
            </div>
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/website/brands/graygrids.svg') }}" alt=""/>
            </div>
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/website/brands/lineicons.svg') }}" alt=""/>
            </div>
            <div class="ud-single-logo">
              <img src="{{ asset('cywise/img/website/brands/ecommerce-html.svg') }}" alt=""/>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- ====== Testimonials End ====== -->

<!-- ====== Back To Top Start ====== -->
<a href="javascript:void(0)" class="back-to-top">
  <i class="lni lni-chevron-up"> </i>
</a>
<!-- ====== Back To Top End ====== -->

<!-- ====== All Javascript Files ====== -->
<script src="{{ asset('cywise/js/website/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('cywise/js/website/wow.min.js') }}"></script>
<script src="{{ asset('cywise/js/website/main.js') }}"></script>
<script>

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
</script>
</body>
</html>
