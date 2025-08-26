<?php

use function Laravel\Folio\{name};

name('a-propos');
?>

<x-layouts.marketing>
  <x-container class="py-0">
    <!-- HERO : BEGIN -->
    <div class="bg-white px-6 py-12 sm:py-16 lg:px-8">
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-5xl font-semibold tracking-tight text-gray-900 sm:text-7xl">
          Cywise, la cybersécurité pour les mortels
        </h2>
        <p class="mt-8 text-pretty text-lg font-medium text-gray-500 sm:text-xl/8">
          Cywise est une plateforme SaaS française qui accompagne les entreprises de toutes tailles dans leur
          <span class="text-indigo-600">cyberprotection</span>, leur <span class="text-indigo-600">cyberdéfense</span>
          et leur <span class="text-indigo-600">cyberrésilience</span>.
        </p>
        <p class="mt-8 text-pretty text-lg font-medium text-gray-500 sm:text-xl/8">
          Notre ambition : rendre la cybersécurité <span class="text-indigo-600">simple</span>, <span
            class="text-indigo-600">accessible</span> et <span class="text-indigo-600">opérationnelle</span>, même sans
          expertise technique.
        </p>
      </div>
    </div>
    <!-- HERO : END -->
    <!-- VISION : BEGIN -->
    <div class="bg-white px-6 py-12 sm:py-16 lg:px-8">
      <div class="mx-auto max-w-3xl text-base/7 text-gray-700">
        <h1 class="mt-2 text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
          La vision
        </h1>
        <p class="mt-6 text-xl/8">
          Chez Cywise, nous voulons rendre la cybersécurité aussi simple et naturelle que de planifier une réunion.
        </p>
        <div class="mt-10 max-w-2xl text-gray-600">
          <p class="mb-2">
            Notre ambition : qu'un jour, "faire un Cywise" devienne un réflexe pour toutes les entreprises. Fini la
            complexité, place à une protection accessible, intuitive et efficace.
          </p>
          <p class="mb-2">
            Nous croyons en une cybersécurité qui n’exclut personne, pas même les plus petites structures.
          </p>
          <p class="mb-2">
            Parce qu'être en sécurité ne devrait jamais être une question de taille.
          </p>
          <p class="mb-2">
            Vous n'avez pas besoin d’être un expert pour protéger votre entreprise.
          </p>
          <ul role="list" class="mt-8 max-w-xl space-y-8 text-gray-600">
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Visibilité complète</strong> sur votre état de sécurité, en continu
              </span>
            </li>
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Automatisation des tâches techniques</strong> pour compenser le manque de ressources internes
              </span>
            </li>
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Chatbot pédagogique</strong> pour répondre à vos questions et vous former en même temps
              </span>
            </li>
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Accompagnement humain</strong> quand c’est nécessaire (ticket, support, hotline)
              </span>
            </li>
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Tarification adaptée aux TPE/PME :</strong> claire, transparente, sans surprise
              </span>
            </li>
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Solution modulaire et évolutive :</strong> vous commencez petit, vous adaptez au fur et à mesure
              </span>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <!-- VISION : END -->
    <!-- HISTORY : BEGIN -->
    <div class="bg-white px-6 py-12 sm:py-16 lg:px-8">
      <div class="mx-auto max-w-3xl text-base/7 text-gray-700">
        <h1 class="mt-2 text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
          La petite histoire
        </h1>
        <div class="mt-10 max-w-2xl text-gray-600">
          <p class="mb-2">
            Cyrille et Pierre se sont rencontrés il y a 15 ans, alors qu'ils étaient encore étudiants, unis par leur
            fascination pour la magie, où la maîtrise de l'illusion et de la dissimulation joue un rôle central. Des
            principes que l’on retrouve aujourd'hui dans les honeypots développés par Cywise, conçus pour tromper et
            piéger les cyberattaquants.
          </p>
          <p class="mb-2">
            Après avoir suivi des parcours professionnels distincts — Cyrille, diplômé du Master Parisien de Recherche
            en Informatique, a fait ses armes chez FactSet dans la gestion de données, tandis que Pierre, diplômé de
            l'EPITA, a œuvré au ministère de l'Intérieur comme spécialiste en cyber offensive — ils ont chacun fondé et
            dirigé leurs propres entreprises.
          </p>
          <p class="mb-2">
            En 2018, ils unissent leurs forces pour créer ComputableFacts, aujourd'hui Cywise, avec une ambition claire
            : rendre la cybersécurité accessible à tous, y compris aux TPE et PME.
          </p>
          <p class="mb-2">
            Cywise est le fruit de quatre années de recherche et développement, où se mêlent expertise en données et
            cybersécurité.
          </p>
        </div>
      </div>
    </div>
    <!-- HISTORY : END -->
    <!-- VALUES : BEGIN -->
    <div class="bg-white px-6 py-12 sm:py-16 lg:px-8">
      <div class="mx-auto max-w-3xl text-base/7 text-gray-700">
        <h1 class="mt-2 text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
          Nos valeurs
        </h1>
        <div class="mt-10 max-w-2xl text-gray-600">
          <p class="mb-2">
            Chez Cywise, on ne fait pas que du code et des audits. On avance avec des convictions :
          </p>
          <ul role="list" class="mt-8 max-w-xl space-y-8 text-gray-600">
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Honnêteté & Intégrité.</strong> "On dit ce qu’on fait. On fait ce qu’on dit". Et on ne vend pas des promesses vides. Ni à nos clients, ni entre nous.
              </span>
            </li>
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Partage & Transmission.</strong> La cybersécurité, on ne la garde pas pour nous. On la simplifie, on la vulgarise, on la transmet. Avec pédagogie. Et quand on peut, avec humour.
              </span>
            </li>
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Curiosité & Persévérance.</strong> Notre IA est curieuse par nature. Et nous aussi. On gratte, on teste, on creuse, on ajuste. Jusqu’à ce que ça fonctionne!
              </span>
            </li>
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Fiabilité.</strong> On déteste les bugs (et les tickets !). Alors on développe proprement, on vérifie, et on livre un produit sérieux.
              </span>
            </li>
            <li class="flex gap-x-3">
              <svg class="mt-1 size-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor"
                   aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
              </svg>
              <span>
                <strong class="font-semibold text-gray-900">Accessibilité & Inclusion.</strong> Pas besoin d’un doctorat en cybersécurité pour utiliser Cywise. Notre solution est pensée pour les TPE/PME. Elle est accessible techniquement et financièrement !
              </span>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <!-- VALUES : END -->
    <!-- TEAM : BEGIN -->
    <div class="bg-white py-24 sm:py-32">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl sm:text-center">
          <h2 class="text-34l text-balance font-semibold tracking-tight text-gray-900 sm:text-5xl">
            L'équipe
          </h2>
          <p class="mt-6 text-lg/8 text-gray-600">
            Nous sommes un groupe dynamique d'individus passionnés par ce que nous faisons et déterminés à offrir les
            meilleures solutions à nos utilisateurs.
          </p>
        </div>
        <ul role="list"
            class="mx-auto mt-20 grid max-w-2xl grid-cols-1 gap-x-6 gap-y-20 sm:grid-cols-2 lg:max-w-4xl lg:gap-x-8 xl:max-w-none">
          <li class="flex flex-col gap-6 xl:flex-row">
            <img class="aspect-[4/5] w-52 flex-none rounded-2xl object-cover"
                 src="{{ asset('/cywise/img/team-csavelief.jpg') }}"
                 alt=""/>
            <div class="flex-auto">
              <h3 class="text-lg/8 font-semibold tracking-tight text-gray-900">
                Cyrille Savelief
              </h3>
              <p class="text-base/7 text-gray-600">
                Président
              </p>
              <p class="mt-6 text-base/7 text-gray-600">
                Cyrille est le président et cofondateur de Cywise. Il apporte à Cywise plus de 15 ans
                d'expérience dans le domaine de la collecte et du traitement de données.
              </p>
            </div>
          </li>
          <li class="flex flex-col gap-6 xl:flex-row">
            <img class="aspect-[4/5] w-52 flex-none rounded-2xl object-cover"
                 src="{{ asset('/cywise/img/team-pduteil.jpg') }}"
                 alt=""/>
            <div class="flex-auto">
              <h3 class="text-lg/8 font-semibold tracking-tight text-gray-900">
                Pierre Duteil
              </h3>
              <p class="text-base/7 text-gray-600">
                Directeur technique
              </p>
              <p class="mt-6 text-base/7 text-gray-600">
                Pierre est le directeur technique et cofondateur de Cywise. Il apporte à Cywise plus
                de 15 ans d'expérience dans le domaine de la cybersécurité offensive et défensive.
              </p>
            </div>
          </li>
          <li class="flex flex-col gap-6 xl:flex-row">
            <img class="aspect-[4/5] w-52 flex-none rounded-2xl object-cover"
                 src="{{ asset('/cywise/img/team-eesmerian.jpg') }}"
                 alt=""/>
            <div class="flex-auto">
              <h3 class="text-lg/8 font-semibold tracking-tight text-gray-900">
                Eric Esmérian
              </h3>
              <p class="text-base/7 text-gray-600">
                Directeur commercial
              </p>
              <p class="mt-6 text-base/7 text-gray-600">
                Eric est dans le développement de startup depuis 2003. Il a rejoint Cywise en mai et depuis
                organise avec Cyrille et Pierre la version industrielle de l’entreprise.
              </p>
            </div>
          </li>
          <li class="flex flex-col gap-6 xl:flex-row">
            <img class="aspect-[4/5] w-52 flex-none rounded-2xl object-cover"
                 src="{{ asset('/cywise/img/team-pbrisacier.jpg') }}"
                 alt=""/>
            <div class="flex-auto">
              <h3 class="text-lg/8 font-semibold tracking-tight text-gray-900">
                Patrick Brisacier
              </h3>
              <p class="text-base/7 text-gray-600">
                R&D
              </p>
              <p class="mt-6 text-base/7 text-gray-600">
                Patrick est le responsable R&D de Cywise. Avec plus de 20 ans d'expérience, Patrick est un
                expert dans la conception, le déploiement et l’optimisation d’infrastructures complexes.
              </p>
            </div>
          </li>
          <li class="flex flex-col gap-6 xl:flex-row">
            <img class="aspect-[4/5] w-52 flex-none rounded-2xl object-cover"
                 src="{{ asset('/cywise/img/team-jjkhalife.jpg') }}"
                 alt=""/>
            <div class="flex-auto">
              <h3 class="text-lg/8 font-semibold tracking-tight text-gray-900">
                Jean-Jamil Khalifé
              </h3>
              <p class="text-base/7 text-gray-600">
                Expert cybersécurité
              </p>
              <p class="mt-6 text-base/7 text-gray-600">
                Jean apporte son expertise de pentester (audit de sécurité) et de chercheur en sécurité informatique en
                améliorant nos outils et règles de détection.
              </p>
            </div>
          </li>
          <li class="flex flex-col gap-6 xl:flex-row">
            <img class="aspect-[4/5] w-52 flex-none rounded-2xl object-cover"
                 src="{{ asset('/cywise/img/team-bguillot.jpg') }}"
                 alt=""/>
            <div class="flex-auto">
              <h3 class="text-lg/8 font-semibold tracking-tight text-gray-900">
                Bérangère Guillot
              </h3>
              <p class="text-base/7 text-gray-600">
                Customer Success
              </p>
              <p class="mt-6 text-base/7 text-gray-600">
                Bérangère apporte son expertise en relation client en collaborant étroitement avec notre équipe pour
                maximiser les bénéfices que nos clients tirent de nos produits et services.
              </p>
            </div>
          </li>
        </ul>
      </div>
    </div>
    <!-- TEAM : END -->
    <!-- CTA : BEGIN -->
    <div class="bg-white py-24 sm:py-32">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
          <p class="text-base/7 font-semibold text-indigo-600">
            Cywise
          </p>
          <h1 class="mt-2 text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
            Prêt à sécuriser votre activité ?
          </h1>
          <div class="mt-10 grid max-w-xl grid-cols-1 gap-8 text-base/7 text-gray-700 lg:max-w-none lg:grid-cols-2">
            <p>
              Cywise s’installe en quelques clics, détecte vos risques automatiquement, et vous accompagne dans toutes
              vos démarches. Avec toujours cyberbuddy à vos côtés !
            </p>
            <ul class="marker:text-indigo-600 list-inside list-disc" role="list">
              <li>Lancer un audit express</li>
              <li>Générer votre PSSI ou charte informatique</li>
              <li>Déployer un honeypot en deux temps trois mouvements</li>
              <li>Être conforme à NIS2 sans complexité</li>
            </ul>
          </div>
          <div class="mt-10 flex items-center gap-x-6 lg:mt-0 lg:shrink-0">
            <a href="{{ route('tools.cybercheck.init') }}"
               class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
              Démarrer mon audit gratuit !
            </a>
            <a href="https://app.cywise.io/register" class="text-sm/6 font-semibold text-gray-900 hover:opacity-80">
              Créer un compte
              <span aria-hidden="true">→</span>
            </a>
          </div>
        </div>
      </div>
    </div>
    <!-- CTA : END -->
  </x-container>
</x-layouts.marketing>
