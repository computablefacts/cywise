<?php

use function Laravel\Folio\{name};

name('home');
?>

<x-layouts.marketing
  :seo="[
        'title'         => setting('site.title', 'Cywise'),
        'description'   => setting('site.description', 'Cybersecurity for the mortals'),
        'image'         => url('/og_image.png'),
        'type'          => 'website'
    ]"
>
  <x-container class="py-0">
    <section>
      <div class="relative isolate px-3 pt-7 lg:px-4">
        <div class="mx-auto max-w-4xl py-16 sm:py-24 lg:py-28">
          <div class="text-center">
            <h1 class="text-balance text-5xl font-semibold tracking-tight text-gray-900 sm:text-7xl">
              Cywise, la <span class="text-indigo-600">cybersécurité</span> simplifiée
            </h1>
            <p class="mt-8 text-pretty text-lg font-medium text-gray-500 sm:text-xl/8">
              Audit cybersécurité, détection de vulnérabilités, PSSI automatisée, alertes personnalisées, avec Cywise,
              protégez vos données et votre activité sans être un expert de la sécurité informatique.
            </p>
          </div>
        </div>
      </div>
    </section>
    <section
      class="flex flex-col items-center justify-between flex-1 w-full max-w-2xl gap-6 px-8 pt-32 mx-auto text-left md:px-12 xl:px-20 lg:pt-32 lg:pb-16 lg:max-w-7xl lg:flex-row">
      <div class="mx-auto max-w-2xl px-6 lg:max-w-7xl lg:px-8">
        <h2 class="text-base/7 font-semibold text-indigo-600">
          Un besoin ?
        </h2>
        <p class="mt-2 text-pretty text-4xl font-semibold tracking-tight text-gray-950 sm:text-5xl">
          Dites-nous qui vous êtes, on adapte le programme !
        </p>
        <p class="mt-3 text-sm/6 text-gray-600">
          Bienvenue ! Dans l’univers Cywise, Cyberbuddy est votre copilote sur tout ce qui touche à la cybersécurité :
          audit, conformité, PSSI... Il est là pour vous guider, vous expliquer, et vous simplifier la vie (pas pour
          vous
          noyer dans le jargon).
        </p>
        <div class="mt-10 grid grid-cols-1 gap-4 sm:mt-16 lg:grid-cols-6 lg:grid-rows-2">
          <div class="relative lg:col-span-3">
            <div class="absolute inset-0 rounded-lg bg-white max-lg:rounded-t-[2rem] lg:rounded-tl-[2rem]"></div>
            <div
              class="relative flex h-full flex-col overflow-hidden rounded-[calc(theme(borderRadius.lg)+1px)] max-lg:rounded-t-[calc(2rem+1px)] lg:rounded-tl-[calc(2rem+1px)]">
              <div class="p-10 pt-10">
                <h3 class="text-sm/4 font-semibold text-indigo-600">
                  TPE & PME
                </h3>
                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">
                  <a href="{{ route('tpe-pme') }}">
                    Je suis une TPE ou une PME <span aria-hidden="true">→</span>
                  </a>
                </p>
              </div>
            </div>
            <div
              class="pointer-events-none absolute inset-0 rounded-lg shadow outline outline-black/5"></div>
          </div>
          <div class="relative lg:col-span-3">
            <div class="absolute inset-0 rounded-lg bg-white lg:rounded-tr-[2rem]"></div>
            <div
              class="relative flex h-full flex-col overflow-hidden rounded-[calc(theme(borderRadius.lg)+1px)] lg:rounded-tr-[calc(2rem+1px)]">
              <div class="p-10 pt-10">
                <h3 class="text-sm/4 font-semibold text-indigo-600">
                  Grand Groupe
                </h3>
                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">
                  <a href="{{ route('pssi') }}">
                    Je suis un grand groupe <span aria-hidden="true">→</span>
                  </a>
                </p>
              </div>
            </div>
            <div
              class="pointer-events-none absolute inset-0 rounded-lg shadow outline outline-black/5"></div>
          </div>
        </div>
      </div>
    </section>
    <section class="bg-white px-6 py-12 sm:py-16 lg:px-8">
      <figure class="mx-auto max-w-2xl">
        <p class="sr-only">5 out of 5 stars</p>
        <div class="flex gap-x-1 text-indigo-600">
          <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd"
                  d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"
                  clip-rule="evenodd"/>
          </svg>
          <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd"
                  d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"
                  clip-rule="evenodd"/>
          </svg>
          <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd"
                  d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"
                  clip-rule="evenodd"/>
          </svg>
          <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd"
                  d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"
                  clip-rule="evenodd"/>
          </svg>
          <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd"
                  d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"
                  clip-rule="evenodd"/>
          </svg>
        </div>
        <blockquote class="mt-10 text-xl/8 font-semibold tracking-tight text-gray-900 sm:text-2xl/9">
          <p>
            “Avec Cywise, nous avons vu produire une PSSI claire et complète pour notre client en quelques jours
            au lieu de plusieurs semaines. Un vrai game changer aussi bien en terme de temps qu'en terme de
            qualité et de suivi.”
          </p>
        </blockquote>
        <figcaption class="mt-10 flex items-center gap-x-6">
          <div class="text-sm/6">
            <div class="font-semibold text-gray-900">Augustin Burg</div>
            <div class="mt-0.5 text-gray-600">Co-fondateur d'Ackero</div>
          </div>
        </figcaption>
      </figure>
    </section>
    <section>
      <div class="bg-white py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
          <div class="mx-auto max-w-2xl lg:text-center">
            <h2 class="text-base/7 font-semibold text-indigo-600">
              Notre différence
            </h2>
            <p class="mt-2 text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl lg:text-balance">
              Pourquoi choisir Cywise pour votre cybersécurité ?
            </p>
          </div>
          <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
            <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
              <div class="relative pl-16">
                <dt class="text-base/7 font-semibold text-gray-900">
                  <div class="absolute left-0 top-0 flex size-10 items-center justify-center rounded-lg bg-indigo-600">
                    <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" aria-hidden="true" data-slot="icon">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"/>
                    </svg>
                  </div>
                  Fiabilité
                </dt>
                <dd class="mt-2 text-base/7 text-gray-600">
                  Surveillance en temps réel, PSSI automatisée, accompagnement par des experts cyber.
                </dd>
              </div>
              <div class="relative pl-16">
                <dt class="text-base/7 font-semibold text-gray-900">
                  <div class="absolute left-0 top-0 flex size-10 items-center justify-center rounded-lg bg-indigo-600">
                    <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" aria-hidden="true" data-slot="icon">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                    </svg>
                  </div>
                  Simplicité
                </dt>
                <dd class="mt-2 text-base/7 text-gray-600">
                  Une plateforme SaaS intuitive, un assistant IA accessible 24/7, des recommandations concrètes et
                  compréhensibles.
                </dd>
              </div>
              <div class="relative pl-16">
                <dt class="text-base/7 font-semibold text-gray-900">
                  <div class="absolute left-0 top-0 flex size-10 items-center justify-center rounded-lg bg-indigo-600">
                    <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" aria-hidden="true" data-slot="icon">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                  </div>
                  Efficacité
                </dt>
                <dd class="mt-2 text-base/7 text-gray-600">
                  Détection proactive des failles, honeypots intégrés, alertes intelligentes, gestion de la surface
                  d'attaque.
                </dd>
              </div>
              <div class="relative pl-16">
                <dt class="text-base/7 font-semibold text-gray-900">
                  <div class="absolute left-0 top-0 flex size-10 items-center justify-center rounded-lg bg-indigo-600">
                    <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" aria-hidden="true" data-slot="icon">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33"/>
                    </svg>
                  </div>
                  Accessibilité
                </dt>
                <dd class="mt-2 text-base/7 text-gray-600">
                  Tarification modulable selon votre taille et vos besoins. ROI immédiat : réduction des interruptions
                  d'activité, protection des données sensibles, conformité garantie.
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </section>
    <section class="bg-white px-6 py-12 sm:py-16 lg:px-8">
      <figure class="mx-auto max-w-2xl">
        <p class="sr-only">5 out of 5 stars</p>
        <div class="flex gap-x-1 text-indigo-600">
          <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd"
                  d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"
                  clip-rule="evenodd"/>
          </svg>
          <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd"
                  d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"
                  clip-rule="evenodd"/>
          </svg>
          <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd"
                  d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"
                  clip-rule="evenodd"/>
          </svg>
          <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd"
                  d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"
                  clip-rule="evenodd"/>
          </svg>
          <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd"
                  d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"
                  clip-rule="evenodd"/>
          </svg>
        </div>
        <blockquote class="mt-10 text-xl/8 font-semibold tracking-tight text-gray-900 sm:text-2xl/9">
          <p>
            “La solution a amélioré notre visibilité des périmètres exposés et internes. Nous avons été notifiés
            automatiquement des vulnérabilités à corriger. Rien de critique, heureusement. Depuis, nous sommes
            alertés dès qu’un changement important est détecté. En somme l’idéal pour une PME comme la nôtre.”
          </p>
        </blockquote>
        <figcaption class="mt-10 flex items-center gap-x-6">
          <div class="text-sm/6">
            <div class="font-semibold text-gray-900">Sylvain M.</div>
            <div class="mt-0.5 text-gray-600">RSSI d'Oppscience</div>
          </div>
        </figcaption>
      </figure>
    </section>
  </x-container>
</x-layouts.marketing>
