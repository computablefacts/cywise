<?php

use function Laravel\Folio\{name};

name('tpe-pme');
?>

<x-layouts.marketing>
  <x-container class="py-0">
    <!-- HERO : BEGIN -->
    <div class="relative isolate overflow-visible bg-white">
      <svg
        class="absolute inset-0 -z-10 size-full stroke-gray-200 [mask-image:radial-gradient(100%_100%_at_top_right,white,transparent)]"
        aria-hidden="true">
        <defs>
          <pattern id="0787a7c5-978c-4f66-83c7-11c213f99cb7" width="200" height="200" x="50%" y="-1"
                   patternUnits="userSpaceOnUse">
            <path d="M.5 200V.5H200" fill="none"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" stroke-width="0" fill="url(#0787a7c5-978c-4f66-83c7-11c213f99cb7)"/>
      </svg>
      <div class="mx-auto max-w-7xl px-6 pb-24 pt-10 sm:pb-32 lg:flex lg:px-8 lg:py-4">
        <div class="mx-auto max-w-2xl lg:mx-0 lg:shrink-0 lg:pt-8">
          <div class="mt-0 sm:mt-0 lg:mt-0">
            <a href="{{ route('changelogs') }}" class="inline-flex space-x-6">
              <span
                class="rounded-full bg-indigo-600/10 px-3 py-1 text-sm/6 font-semibold text-indigo-600 ring-1 ring-inset ring-indigo-600/10">
                {{ __('What\'s new') }}
              </span>
            </a>
          </div>
          <h1 class="mt-10 text-pretty text-5xl font-semibold tracking-tight text-gray-900 sm:text-7xl">
            La solution de cybersécurité pour TPE et PME
          </h1>
          <p class="mt-8 text-pretty text-lg font-medium text-gray-500 sm:text-xl/8">
            CyberBuddy, notre assistant cyber, vous aide chaque semaine à améliorer progressivement la sécurité de votre
            système d'information.
          </p>
          <div class="mt-10 flex items-center gap-x-6">
            <a href="{{ route('tools.cybercheck.init') }}"
               class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
              Démarrez l’audit gratuit maintenant !
            </a>
            <a href="{{ route('register') }}" class="text-sm/6 font-semibold text-gray-900">
              Créer un compte <span aria-hidden="true">→</span>
            </a>
          </div>
        </div>
        <div class="mx-auto mt-16 flex max-w-2xl sm:mt-24 lg:ml-10 lg:mr-0 lg:mt-0 lg:max-w-none lg:flex-none xl:ml-3">
          <div class="max-w-3xl flex-none sm:max-w-5xl lg:max-w-none">
            <div
              class="-m-2 rounded-xl bg-gray-900/5 p-2 ring-1 ring-inset ring-gray-900/10 lg:-m-4 lg:rounded-2xl lg:p-4">
              <img src="/cywise/img/screenshot.png" alt="App Screenshot" width="2432" height="1442"
                   class="w-[76rem] rounded-md shadow-2xl ring-1 ring-gray-900/10"/>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- HERO : END -->
    <!-- SOCIAL PROOF : BEGIN -->
    <div class="bg-white py-24 sm:py-32">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <h2 class="text-center text-lg/8 font-semibold text-gray-900">
          Ils nous font confiance :
        </h2>
        <div
          class="mx-auto mt-10 grid max-w-lg grid-cols-4 items-center gap-x-8 gap-y-10 sm:max-w-xl sm:grid-cols-6 sm:gap-x-10 lg:mx-0 lg:max-w-none lg:grid-cols-5">
          <img class="col-span-2 max-h-12 w-full object-contain lg:col-span-1" src="/cywise/img/logo-elephantastic.jpg"
               alt="Elephantastic" width="158" height="48"/>
          <img class="col-span-2 max-h-12 w-full object-contain lg:col-span-1" src="/cywise/img/logo-oppscience.svg"
               alt="Oppscience" width="158" height="48"/>
          <img class="col-span-2 max-h-12 w-full object-contain lg:col-span-1" src="/cywise/img/logo-netemedia.png"
               alt="Netemedia" width="158" height="48"/>
          <img class="col-span-2 max-h-12 w-full object-contain lg:col-span-1" src="/cywise/img/logo-ista.png"
               alt="ISTA" width="158" height="48"/>
          <img class="col-span-2 max-h-12 w-full object-contain lg:col-span-1" src="/cywise/img/logo-hermes.png"
               alt="Hermès" width="158" height="48"/>
        </div>
      </div>
    </div>
    <!-- SOCIAL PROOF : END -->
    <!-- PROBLEMS : BEGIN -->
    <div class="bg-white py-24 sm:py-32">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl lg:text-center">
          <h2 class="text-base/7 font-semibold text-indigo-600">
            La cybersécurité simplifiée
          </h2>
          <p class="mt-2 text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl lg:text-balance">
            5 minutes par semaine pour sécuriser votre système d’information
          </p>
          <p class="mt-6 text-lg/8 text-gray-600">
            Recevez chaque semaine dans votre boite mail un bulletin d'information personnalisé vous informant des
            problèmes de sécurité de votre système d’information.
          </p>
        </div>
        <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
          <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
            <div class="flex flex-col">
              <dt class="flex items-center gap-x-3 text-base/7 font-semibold text-gray-900">
                <svg class="size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                     data-slot="icon">
                  <path fill-rule="evenodd"
                        d="M5.5 17a4.5 4.5 0 0 1-1.44-8.765 4.5 4.5 0 0 1 8.302-3.046 3.5 3.5 0 0 1 4.504 4.272A4 4 0 0 1 15 17H5.5Zm3.75-2.75a.75.75 0 0 0 1.5 0V9.66l1.95 2.1a.75.75 0 1 0 1.1-1.02l-3.25-3.5a.75.75 0 0 0-1.1 0l-3.25 3.5a.75.75 0 1 0 1.1 1.02l1.95-2.1v4.59Z"
                        clip-rule="evenodd"/>
                </svg>
                Sites web & Serveurs
              </dt>
              <dd class="mt-4 flex flex-auto flex-col text-base/7 text-gray-600">
                <p class="flex-auto">
                  {!! __('Cywise <b>surveille</b> en permanence vos sites web et serveurs exposés sur internet, détecte
                  vos vulnérabilités et vous alerte avant qu’un attaquant ne les exploite.') !!}
                </p>
              </dd>
            </div>
            <div class="flex flex-col">
              <dt class="flex items-center gap-x-3 text-base/7 font-semibold text-gray-900">
                <svg class="size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                     data-slot="icon">
                  <path fill-rule="evenodd"
                        d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z"
                        clip-rule="evenodd"/>
                </svg>
                Identifiants de connexion
              </dt>
              <dd class="mt-4 flex flex-auto flex-col text-base/7 text-gray-600">
                <p class="flex-auto">
                  {!! __('Cywise <b>explore</b> le web pour trouver les identifiants compromis de vos employés ou
                  prestataires et vous informe immédiatement de toute menace potentielle.') !!}
                </p>
              </dd>
            </div>
            <div class="flex flex-col">
              <dt class="flex items-center gap-x-3 text-base/7 font-semibold text-gray-900">
                <svg class="size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                     data-slot="icon">
                  <path fill-rule="evenodd"
                        d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z"
                        clip-rule="evenodd"/>
                </svg>
                Politiques de sécurité
              </dt>
              <dd class="mt-4 flex flex-auto flex-col text-base/7 text-gray-600">
                <p class="flex-auto">
                  {!! __('Cywise vous <b>accompagne</b> dans la création et la diffusion de votre charte informatique et
                  de votre politique de sécurité auprès de vos employés.') !!}
                </p>
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
    <!-- PROBLEMS : END -->
    <!-- SOLUTION : BEGIN -->
    <div class="overflow-hidden bg-white py-24 sm:py-32">
      <div class="mx-auto max-w-2xl px-6 lg:max-w-7xl lg:px-8">
        <div class="max-w-4xl">
          <p class="text-base/7 font-semibold text-indigo-600">
            Un écosystème complet
          </p>
          <h1 class="mt-2 text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
            Pourquoi choisir Cywise ?
          </h1>
          <p class="mt-6 text-balance text-xl/8 text-gray-700">
            Cywise se distingue en étant la première plateforme SaaS à offrir une solution de cybersécurité complète
            pour les PME et TPE intégrant les trois piliers de la cybersécurité : la cyberprotection, la cyberdéfense et
            la cyberrésilience.
          </p>
        </div>
        <section class="mt-20 grid grid-cols-1 lg:grid-cols-2 lg:gap-x-8 lg:gap-y-16">
          <div class="lg:pr-8">
            <h2 class="text-pretty text-2xl font-semibold tracking-tight text-gray-900">
              Cyberprotection
            </h2>
            <p class="mt-6 text-base/7 text-gray-600">
              La cyberprotection englobe toutes les mesures et pratiques mises en place pour prévenir les cyberattaques
              et sécuriser les systèmes d'information contre les intrusions et les abus. Elle s'appuie sur une approche
              proactive visant à réduire les vulnérabilités des systèmes informatiques et à minimiser les risques de
              compromission.
            </p>
            <h2 class="mt-6 text-pretty text-2xl font-semibold tracking-tight text-gray-900">
              Cyberdéfense
            </h2>
            <p class="mt-6 text-base/7 text-gray-600">
              Lorsque la cyberprotection ne parvient pas à prévenir une attaque, la cyberdéfense prend le relais. La
              cyberdéfense se concentre sur la détection d'activités suspectes, la surveillance continue des réseaux et
              la réponse aux incidents de sécurité. Son objectif est d'identifier rapidement les menaces, de contenir
              les attaques en cours et de minimiser les impacts potentiels.
            </p>
            <h2 class="mt-6 text-pretty text-2xl font-semibold tracking-tight text-gray-900">
              Cyberrésilience
            </h2>
            <p class="mt-6 text-base/7 text-gray-600">
              Bien qu'une cyberprotection et une cyberdéfense solides soient mises en place, il est impossible de
              garantir une sécurité absolue. La cyberrésilience vise à maintenir la continuité des activités malgré les
              cyberattaques et à se remettre rapidement des incidents. La cyberrésilience inclut la mise en place d'une
              Politique de Sécurité des Systèmes d'Information (PSSI) pragmatique et sa mise en application, la
              sensibilisation des employés aux enjeux de cybersécurité ainsi que l'évaluation régulière de la capacité
              de l'organisation à réagir face à une cyberattaque.
            </p>
          </div>
          <div class="pt-16 lg:row-span-2 lg:-mr-16 xl:mr-auto">
            <div class="-mx-8 grid grid-cols-2 gap-4 sm:-mx-16 sm:grid-cols-4 lg:mx-0 lg:grid-cols-2 lg:gap-4 xl:gap-8">
              <div
                class="aspect-square overflow-hidden rounded-xl shadow-xl outline outline-1 -outline-offset-1 outline-black/10">
                <img alt=""
                     src="https://images.unsplash.com/photo-1590650516494-0c8e4a4dd67e?&auto=format&fit=crop&crop=center&w=560&h=560&q=90"
                     class="block size-full object-cover"/>
              </div>
              <div
                class="-mt-8 aspect-square overflow-hidden rounded-xl shadow-xl outline outline-1 -outline-offset-1 outline-black/10 lg:-mt-40">
                <img alt=""
                     src="https://images.unsplash.com/photo-1557804506-669a67965ba0?&auto=format&fit=crop&crop=left&w=560&h=560&q=90"
                     class="block size-full object-cover"/>
              </div>
              <div
                class="aspect-square overflow-hidden rounded-xl shadow-xl outline outline-1 -outline-offset-1 outline-black/10">
                <img alt=""
                     src="https://images.unsplash.com/photo-1559136555-9303baea8ebd?&auto=format&fit=crop&crop=left&w=560&h=560&q=90"
                     class="block size-full object-cover"/>
              </div>
              <div
                class="-mt-8 aspect-square overflow-hidden rounded-xl shadow-xl outline outline-1 -outline-offset-1 outline-black/10 lg:-mt-40">
                <img alt=""
                     src="https://images.unsplash.com/photo-1598257006458-087169a1f08d?&auto=format&fit=crop&crop=center&w=560&h=560&q=90"
                     class="block size-full object-cover"/>
              </div>
            </div>
          </div>
          @php
          $user = \App\Models\User::where('email', config('towerify.admin.email'))->firstOrFail();
          $nbUsers = format_number(\App\Models\User::count());
          $nbAssets = format_number(\App\Models\Asset::count() + \App\Models\YnhServer::count());
          $nbHoneypots = format_number(\App\Models\Honeypot::count());
          $nbLeaks = format_number(\App\Helpers\JosianneClient::numberOfRows('dumps_login_email_domain'));
          @endphp
          <div class="max-lg:mt-16 lg:col-span-1">
            <p class="text-base/7 font-semibold text-gray-500">
              La société en chiffres
            </p>
            <hr class="mt-6 border-t border-gray-200"/>
            <dl class="mt-6 grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
              <div class="flex flex-col gap-y-2 border-b border-dotted border-gray-200 pb-4">
                <dt class="text-sm/6 text-gray-600">
                  Users
                </dt>
                <dd class="order-first text-6xl font-semibold tracking-tight">
                  <span>{{ $nbUsers[0] }}</span> {{ $nbUsers[1] }}
                </dd>
              </div>
              <div class="flex flex-col gap-y-2 border-b border-dotted border-gray-200 pb-4">
                <dt class="text-sm/6 text-gray-600">
                  Monitored Servers
                </dt>
                <dd class="order-first text-6xl font-semibold tracking-tight">
                  <span>{{ $nbAssets[0] }}</span> {{ $nbAssets[1] }}
                </dd>
              </div>
              <div
                class="flex flex-col gap-y-2 max-sm:border-b max-sm:border-dotted max-sm:border-gray-200 max-sm:pb-4">
                <dt class="text-sm/6 text-gray-600">
                  Compromised Credentials
                </dt>
                <dd class="order-first text-6xl font-semibold tracking-tight">
                  <span>{{ $nbLeaks[0] }}</span> {{ $nbLeaks[1] }}
                </dd>
              </div>
              <div class="flex flex-col gap-y-2">
                <dt class="text-sm/6 text-gray-600">
                  Honeypots
                </dt>
                <dd class="order-first text-6xl font-semibold tracking-tight">
                  <span>{{ $nbHoneypots[0] }}</span> {{ $nbHoneypots[1] }}
                </dd>
              </div>
            </dl>
          </div>
        </section>
      </div>
    </div>
    <!-- SOLUTION : END -->
    <!-- FEATURES : BEGIN-->
    <div class="bg-white py-24 sm:py-32">
      <div class="mx-auto max-w-2xl px-6 lg:max-w-7xl lg:px-8">
        <h2 class="text-base/7 font-semibold text-indigo-600">
          Une approche globale
        </h2>
        <p class="mt-2 max-w-lg text-pretty text-4xl font-semibold tracking-tight text-gray-950 sm:text-5xl">
          Une solution tout-en-un de cybersécurité pour TPE et PME
        </p>
        <div class="mt-10 grid grid-cols-1 gap-4 sm:mt-16 lg:grid-cols-6 lg:grid-rows-2">
          <div class="relative lg:col-span-3">
            <div class="absolute inset-0 rounded-lg bg-white max-lg:rounded-t-[2rem] lg:rounded-tl-[2rem]"></div>
            <div
              class="relative flex h-full flex-col overflow-hidden rounded-[calc(theme(borderRadius.lg)+1px)] max-lg:rounded-t-[calc(2rem+1px)] lg:rounded-tl-[calc(2rem+1px)]">
              <img class="h-96 object-cover object-top" src="/cywise/img/cyberbuddy.png" alt=""/>
              <div class="p-10 pt-4">
                <h3 class="text-sm/4 font-semibold text-indigo-600">
                  Comprendre
                </h3>
                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">
                  CyberBuddy
                </p>
                <p class="mt-2 max-w-lg text-sm/6 text-gray-600">
                  Votre assistant cyber vous aide à déterminer et à comprendre les bonnes pratiques pour sécuriser votre
                  système d'information.
                </p>
              </div>
            </div>
            <div
              class="pointer-events-none absolute inset-0 rounded-lg shadow outline outline-black/5 max-lg:rounded-t-[2rem] lg:rounded-tl-[2rem]"></div>
          </div>
          <div class="relative lg:col-span-3">
            <div class="absolute inset-0 rounded-lg bg-white lg:rounded-tr-[2rem]"></div>
            <div
              class="relative flex h-full flex-col overflow-hidden rounded-[calc(theme(borderRadius.lg)+1px)] lg:rounded-tr-[calc(2rem+1px)]">
              <img class="h-96 object-cover object-top" src="/cywise/img/cybertodo.png" alt=""/>
              <div class="p-10 pt-4">
                <h3 class="text-sm/4 font-semibold text-indigo-600">
                  Prioriser
                </h3>
                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">
                  CyberTODO
                </p>
                <p class="mt-2 max-w-lg text-sm/6 text-gray-600">
                  Nous classons par ordre de priorité les actions correctives à mener pour vous aider à rester concentré
                  sur l'essentiel.
                </p>
              </div>
            </div>
            <div
              class="pointer-events-none absolute inset-0 rounded-lg shadow outline outline-black/5 lg:rounded-tr-[2rem]"></div>
          </div>
          <div class="relative lg:col-span-2">
            <div class="absolute inset-0 rounded-lg bg-white lg:rounded-bl-[2rem]"></div>
            <div
              class="relative flex h-full flex-col overflow-hidden rounded-[calc(theme(borderRadius.lg)+1px)] lg:rounded-bl-[calc(2rem+1px)]">
              <img class="h-96 object-cover object-left" src="/cywise/img/vulnerability.png" alt=""/>
              <div class="p-10 pt-4">
                <h3 class="text-sm/4 font-semibold text-indigo-600">
                  Surveiller
                </h3>
                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">
                  Scanner de vulnérabilités
                </p>
                <p class="mt-2 max-w-lg text-sm/6 text-gray-600">
                  Effectue des tests hebdomadaires de plus de 50 000 vulnérabilités, constamment mises à jour, sur vos
                  serveurs web et noms de domaine.
                </p>
              </div>
            </div>
            <div
              class="pointer-events-none absolute inset-0 rounded-lg shadow outline outline-black/5 lg:rounded-bl-[2rem]"></div>
          </div>
          <div class="relative lg:col-span-2">
            <div class="absolute inset-0 rounded-lg bg-white"></div>
            <div class="relative flex h-full flex-col overflow-hidden rounded-[calc(theme(borderRadius.lg)+1px)]">
              <img class="h-96 object-cover object-left" src="/cywise/img/events.png" alt=""/>
              <div class="p-10 pt-4">
                <h3 class="text-sm/4 font-semibold text-indigo-600">
                  Détecter
                </h3>
                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">
                  Agents
                </p>
                <p class="mt-2 max-w-lg text-sm/6 text-gray-600">
                  Assure une collecte continue des événements de sécurité émis par vos serveurs et vous alerte en cas de
                  comportement suspect.
                </p>
              </div>
            </div>
            <div class="pointer-events-none absolute inset-0 rounded-lg shadow outline outline-black/5"></div>
          </div>
          <div class="relative lg:col-span-2">
            <div class="absolute inset-0 rounded-lg bg-white max-lg:rounded-b-[2rem] lg:rounded-br-[2rem]"></div>
            <div
              class="relative flex h-full flex-col overflow-hidden rounded-[calc(theme(borderRadius.lg)+1px)] max-lg:rounded-b-[calc(2rem+1px)] lg:rounded-br-[calc(2rem+1px)]">
              <img class="h-96 object-cover object-left" src="/cywise/img/honeypots.png" alt=""/>
              <div class="p-10 pt-4">
                <h3 class="text-sm/4 font-semibold text-indigo-600">
                  Anticiper
                </h3>
                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">
                  Honeypots
                </p>
                <p class="mt-2 max-w-lg text-sm/6 text-gray-600">
                  Détourne les cybercriminels vers des leurres pour identifier les menaces avant qu'elles n'atteignent
                  vos systèmes réels.
                </p>
              </div>
            </div>
            <div
              class="pointer-events-none absolute inset-0 rounded-lg shadow outline outline-black/5 max-lg:rounded-b-[2rem] lg:rounded-br-[2rem]"></div>
          </div>
        </div>
      </div>
    </div>
    <!-- FEATURES : END -->
    <!-- TESTIMONIALS : BEGIN -->
    <section class="bg-white py-24 sm:py-32">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto grid max-w-2xl grid-cols-1 lg:mx-0 lg:max-w-none lg:grid-cols-2">
          <div class="flex flex-col pb-10 sm:pb-16 lg:pb-0 lg:pr-8 xl:pr-20">
            <img class="h-12 self-start" src="/cywise/img/logo-oppscience.svg" alt=""/>
            <figure class="mt-10 flex flex-auto flex-col justify-between">
              <blockquote class="text-lg/8 text-gray-900">
                <p>
                  “La solution a amélioré notre visibilité des périmètres exposés et internes. Nous avons été notifiés
                  automatiquement des vulnérabilités à corriger. Rien de critique, heureusement. Depuis, nous sommes
                  alertés dès qu’un changement important est détecté. En somme l’idéal pour une PME comme la nôtre.”
                </p>
              </blockquote>
              <figcaption class="mt-10 flex items-center gap-x-6">
                <div class="text-base">
                  <div class="font-semibold text-gray-900">
                    Sylvain M.
                  </div>
                  <div class="mt-1 text-gray-500">
                    RSSI d'Oppscience
                  </div>
                </div>
              </figcaption>
            </figure>
          </div>
          <div class="flex flex-col pt-10 sm:pt-16 lg:pl-8 lg:pt-0 xl:pl-20">
            <img class="h-12 self-start" src="/cywise/img/logo-elephantastic.jpg" alt=""/>
            <figure class="mt-10 flex flex-auto flex-col justify-between">
              <blockquote class="text-lg/8 text-gray-900">
                <p>
                  “Excepteur veniam labore ullamco eiusmod. Pariatur consequat proident duis dolore nulla veniam
                  reprehenderit nisi officia voluptate incididunt exercitation exercitation elit. Nostrud veniam sint
                  dolor nisi ullamco.”
                </p>
              </blockquote>
              <figcaption class="mt-10 flex items-center gap-x-6">
                <div class="text-base">
                  <div class="font-semibold text-gray-900">
                    John Doe
                  </div>
                  <div class="mt-1 text-gray-500">
                    CEO d'Elephantastic
                  </div>
                </div>
              </figcaption>
            </figure>
          </div>
        </div>
      </div>
    </section>
    <!-- TESTIMONIALS : END -->
    <!-- PRICING : BEGIN -->
    <x-marketing.sections.pricing/>
    <!-- PRICING : END -->
    <!-- CTA : BEGIN -->
    <div class="bg-white">
      <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:flex lg:items-center lg:justify-between lg:px-8">
        <h2 class="max-w-2xl text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
          {!! __('Vous souhaitez évaluer la sécurité de votre site web?') !!}
        </h2>
        <div class="mt-10 flex items-center gap-x-6 lg:mt-0 lg:shrink-0">
          <a href="{{ route('tools.cybercheck.init') }}"
             class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Démarrez l’audit gratuit maintenant !
          </a>
        </div>
      </div>
    </div>
    <!-- CTA : END -->
  </x-container>
</x-layouts.marketing>
