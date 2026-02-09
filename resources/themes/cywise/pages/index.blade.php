<?php

use function Laravel\Folio\{name};

name('home');
?>

<x-layouts.marketing
    :seo="[
        'title'         => setting('site.title', 'Cywise'),
        'description'   => setting('site.description', 'Cybersecurity for the mortals'),
        'image'         => url('/cywise/img/screenshot.png'),
        'type'          => 'website'
    ]"
>
  <!-- ===== All CSS files ===== -->
  <link rel="stylesheet" href="{{ asset('cywise/css/bootstrap.min.css') }}"/>
  <link rel="stylesheet" href="{{ asset('cywise/css/animate.css') }}"/>
  <link rel="stylesheet" href="{{ asset('cywise/css/lineicons.css') }}"/>
  <link rel="stylesheet" href="{{ asset('cywise/css/ud-styles.css') }}"/>

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
              <!-- Une équipe cyber, disponible 24/7 — dans votre poche. -->
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
                src="{{ asset('cywise/img/hero/dotted-shape.svg') }}"
                alt="shape"
                class="shape shape-1"
            />
            <img
                src="{{ asset('cywise/img/hero/dotted-shape.svg') }}"
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
            <span>Features</span>
            <h2>Main Features of Play</h2>
            <p>
              There are many variations of passages of Lorem Ipsum available
              but the majority have suffered alteration in some form.
            </p>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-xl-3 col-lg-3 col-sm-6">
          <div class="ud-single-feature wow fadeInUp" data-wow-delay=".1s">
            <div class="ud-feature-icon">
              <i class="lni lni-gift"></i>
            </div>
            <div class="ud-feature-content">
              <h3 class="ud-feature-title">Free and Open-Source</h3>
              <p class="ud-feature-desc">
                Lorem Ipsum is simply dummy text of the printing and industry.
              </p>
              <a href="javascript:void(0)" class="ud-feature-link">
                Learn More
              </a>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-sm-6">
          <div class="ud-single-feature wow fadeInUp" data-wow-delay=".15s">
            <div class="ud-feature-icon">
              <i class="lni lni-move"></i>
            </div>
            <div class="ud-feature-content">
              <h3 class="ud-feature-title">Multipurpose Template</h3>
              <p class="ud-feature-desc">
                Lorem Ipsum is simply dummy text of the printing and industry.
              </p>
              <a href="javascript:void(0)" class="ud-feature-link">
                Learn More
              </a>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-sm-6">
          <div class="ud-single-feature wow fadeInUp" data-wow-delay=".2s">
            <div class="ud-feature-icon">
              <i class="lni lni-layout"></i>
            </div>
            <div class="ud-feature-content">
              <h3 class="ud-feature-title">High-quality Design</h3>
              <p class="ud-feature-desc">
                Lorem Ipsum is simply dummy text of the printing and industry.
              </p>
              <a href="javascript:void(0)" class="ud-feature-link">
                Learn More
              </a>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-sm-6">
          <div class="ud-single-feature wow fadeInUp" data-wow-delay=".25s">
            <div class="ud-feature-icon">
              <i class="lni lni-layers"></i>
            </div>
            <div class="ud-feature-content">
              <h3 class="ud-feature-title">All Essential Elements</h3>
              <p class="ud-feature-desc">
                Lorem Ipsum is simply dummy text of the printing and industry.
              </p>
              <a href="javascript:void(0)" class="ud-feature-link">
                Learn More
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- ====== Features End ====== -->

  <!-- ====== About Start ====== -->
  <section id="about" class="ud-about">
    <div class="container">
      <div class="ud-about-wrapper wow fadeInUp" data-wow-delay=".2s">
        <div class="ud-about-content-wrapper">
          <div class="ud-about-content">
            <span class="tag">About Us</span>
            <h2>Brilliant Toolkit to Build Nextgen Website Faster.</h2>
            <p>
              The main ‘thrust’ is to focus on educating attendees on how to
              best protect highly vulnerable business applications with
              interactive panel discussions and roundtables led by subject
              matter experts.
            </p>
            <p>
              The main ‘thrust’ is to focus on educating attendees on how to
              best protect highly vulnerable business applications with
              interactive panel.
            </p>
            <a href="javascript:void(0)" class="ud-main-btn">Learn More</a>
          </div>
        </div>
        <div class="ud-about-image">
          <img src="{{ asset('cywise/img/about/about-image.svg') }}" alt="about-image"/>
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
          <div
              class="ud-single-pricing first-item wow fadeInUp"
              data-wow-delay=".15s"
          >
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
          <div
              class="ud-single-pricing active wow fadeInUp"
              data-wow-delay=".1s"
          >
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
          <div
              class="ud-single-pricing last-item wow fadeInUp"
              data-wow-delay=".15s"
          >
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
      <img src="{{ asset('cywise/img/faq/shape.svg') }}" alt="shape" />
    </div>
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <div class="ud-section-title text-center mx-auto">
            <span>FAQ</span>
            <h2>Any Questions? Answered</h2>
            <p>
              There are many variations of passages of Lorem Ipsum available
              but the majority have suffered alteration in some form.
            </p>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-6">
          <div class="ud-single-faq wow fadeInUp" data-wow-delay=".1s">
            <div class="accordion">
              <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseOne"
              >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                <span>How to use UIdeck?</span>
              </button>
              <div id="collapseOne" class="accordion-collapse collapse">
                <div class="ud-faq-body">
                  Lorem Ipsum is simply dummy text of the printing and
                  typesetting industry. Lorem Ipsum has been the industry's
                  standard dummy text ever since the 1500s, when an unknown
                  printer took a galley of type and scrambled it to make a
                  type specimen book.
                </div>
              </div>
            </div>
          </div>
          <div class="ud-single-faq wow fadeInUp" data-wow-delay=".15s">
            <div class="accordion">
              <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseTwo"
              >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                <span>How to download icons from Lineicons?</span>
              </button>
              <div id="collapseTwo" class="accordion-collapse collapse">
                <div class="ud-faq-body">
                  Lorem Ipsum is simply dummy text of the printing and
                  typesetting industry. Lorem Ipsum has been the industry's
                  standard dummy text ever since the 1500s, when an unknown
                  printer took a galley of type and scrambled it to make a
                  type specimen book.
                </div>
              </div>
            </div>
          </div>
          <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
            <div class="accordion">
              <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseThree"
              >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                <span>Is GrayGrids part of UIdeck?</span>
              </button>
              <div id="collapseThree" class="accordion-collapse collapse">
                <div class="ud-faq-body">
                  Lorem Ipsum is simply dummy text of the printing and
                  typesetting industry. Lorem Ipsum has been the industry's
                  standard dummy text ever since the 1500s, when an unknown
                  printer took a galley of type and scrambled it to make a
                  type specimen book.
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="ud-single-faq wow fadeInUp" data-wow-delay=".1s">
            <div class="accordion">
              <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseFour"
              >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                <span>Can I use this template for commercial project?</span>
              </button>
              <div id="collapseFour" class="accordion-collapse collapse">
                <div class="ud-faq-body">
                  Lorem Ipsum is simply dummy text of the printing and
                  typesetting industry. Lorem Ipsum has been the industry's
                  standard dummy text ever since the 1500s, when an unknown
                  printer took a galley of type and scrambled it to make a
                  type specimen book.
                </div>
              </div>
            </div>
          </div>
          <div class="ud-single-faq wow fadeInUp" data-wow-delay=".15s">
            <div class="accordion">
              <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseFive"
              >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                <span>Do you have plan releasing Play Pro?</span>
              </button>
              <div id="collapseFive" class="accordion-collapse collapse">
                <div class="ud-faq-body">
                  Lorem Ipsum is simply dummy text of the printing and
                  typesetting industry. Lorem Ipsum has been the industry's
                  standard dummy text ever since the 1500s, when an unknown
                  printer took a galley of type and scrambled it to make a
                  type specimen book.
                </div>
              </div>
            </div>
          </div>
          <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
            <div class="accordion">
              <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseSix"
              >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                <span>Where and how to host this template?</span>
              </button>
              <div id="collapseSix" class="accordion-collapse collapse">
                <div class="ud-faq-body">
                  Lorem Ipsum is simply dummy text of the printing and
                  typesetting industry. Lorem Ipsum has been the industry's
                  standard dummy text ever since the 1500s, when an unknown
                  printer took a galley of type and scrambled it to make a
                  type specimen book.
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
          <div
              class="ud-single-testimonial wow fadeInUp"
              data-wow-delay=".1s"
          >
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
                <img
                    src="{{ asset('cywise/img/testimonials/author-01.png') }}"
                    alt="author"
                />
              </div>
              <div class="ud-testimonial-meta">
                <h4>Sabo Masties</h4>
                <p>Founder @UIdeck</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div
              class="ud-single-testimonial wow fadeInUp"
              data-wow-delay=".15s"
          >
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
                <img
                    src="{{ asset('cywise/img/testimonials/author-02.png') }}"
                    alt="author"
                />
              </div>
              <div class="ud-testimonial-meta">
                <h4>Margin Gesmu</h4>
                <p>Founder @Lineicons</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div
              class="ud-single-testimonial wow fadeInUp"
              data-wow-delay=".2s"
          >
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
                <img
                    src="{{ asset('cywise/img/testimonials/author-03.png') }}"
                    alt="author"
                />
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
                <img src="{{ asset('cywise/img/brands/ayroui.svg') }}" alt="" />
              </div>
              <div class="ud-single-logo">
                <img src="{{ asset('cywise/img/brands/uideck.svg') }}" alt="" />
              </div>
              <div class="ud-single-logo">
                <img src="{{ asset('cywise/img/brands/graygrids.svg') }}" alt="" />
              </div>
              <div class="ud-single-logo">
                <img
                    src="{{ asset('cywise/img/brands/lineicons.svg') }}"
                    alt="lineicons"
                />
              </div>
              <div class="ud-single-logo">
                <img src="{{ asset('cywise/img/brands/ecommerce-html.svg') }}" alt="" />
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
  <script src="{{ asset('cywise/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('cywise/js/wow.min.js') }}"></script>
  <script src="{{ asset('cywise/js/main.js') }}"></script>
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
</x-layouts.marketing>
