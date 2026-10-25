@php
    // Audience page, shared by pages/for-whom/* (fr) and pages/en/for-whom/* (en).
    // $audience: smbs | startups | it-teams | cisos | msps
    $isEnglish = $locale === 'en';
    $t = fn (string $fr, string $en) => $isEnglish ? $en : $fr;
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
    $wrap = 'ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:sm:px-6';

    // Same catalogue as the home page; tones fixed per solution (see site/edge-card)
    $solutions = [
        'attack-surface' => ['critical', $t('Surface d’attaque', 'Attack surface'), $t('Visualisez ce que les attaquants voient.', 'See what attackers see.'), $t('Surveillez vos domaines, serveurs et services exposés en temps réel.', 'Monitor public domains, servers, ports and exposed services.')],
        'vulnerability-management' => ['medium', $t('Vulnérabilités', 'Vulnerabilities'), $t('Détectez vos points faibles.', 'Find the weak points.'), $t('Identifiez vos vulnérabilités et concentrez-vous sur les risques les plus critiques.', 'Identify vulnerabilities and focus on the most important risks.')],
        'credential-monitoring' => ['low', $t('Identifiants', 'Credentials'), $t('Découvrez vos données exposées.', 'Know what leaked.'), $t('Détectez les identifiants compromis liés à votre entreprise sur le web.', 'Detect compromised credentials linked to your company.')],
        'cyberbuddy' => ['brand', 'CyberBuddy', $t('Interrogez. Comprenez. Agissez.', 'Ask. Understand. Act.'), $t('Obtenez des conseils clairs adaptés au contexte de votre entreprise.', 'Get clear cybersecurity guidance from your company context.')],
        'pentest' => ['ink', 'Pentest', $t('Faites tester vos systèmes par nos experts.', 'Put humans on the attack.'), $t('Faites tester vos applications critiques par nos experts en sécurité offensive.', 'Test critical applications with experienced security experts.')],
    ];

    // Per audience: title [lead, accent], intro, hero rows [level, text, tag, badge] (example data), features, solutions
    $pages = [
        'smbs' => [
            'title' => [$t('La sécurité simplifiée pour votre', 'Security without a large'), $t('entreprise.', 'security team.')],
            'intro' => $t('Bénéficiez d’une visibilité claire et d’actions concrètes pour sécuriser votre activité sans équipe dédiée.', 'Give small and medium businesses clear visibility and practical actions.'),
            'panel' => 'acme.fr',
            'rows' => [
                ['critical', $t('Portail d’administration exposé', 'Exposed admin portal'), 'admin.acme.fr', $t('Critique', 'Critical')],
                ['high', $t('Identifiant compromis', 'Leaked credential'), 'compta@acme.fr', $t('Élevé', 'High')],
                ['medium', $t('Configuration TLS', 'TLS configuration'), 'www.acme.fr', $t('Moyen', 'Medium')],
            ],
            'features' => [
                [$t('Visibilité immédiate', 'Simple visibility'), $t('Identifiez vos expositions critiques sans outils complexes.', 'See important exposure without specialist tools.')],
                [$t('Remédiation guidée', 'Guided fixes'), $t('Appliquez directement nos conseils pour corriger vos vulnérabilités.', 'Turn technical findings into direct actions.')],
                [$t('Pilotage simplifié', 'Clear reporting'), $t('Suivez et partagez votre niveau de sécurité en toute simplicité.', 'Share security status with management.')],
                [$t('Sérénité continue', 'Continuous protection'), $t('Maintenez votre surveillance active 24/7 après votre première évaluation.', 'Keep monitoring after the first assessment.')],
            ],
            'solutions' => ['attack-surface', 'credential-monitoring', 'cyberbuddy'],
        ],
        'startups' => [
            'title' => [$t('Accélérez votre croissance en toute', 'Move fast without ignoring'), $t('sécurité.', 'security.')],
            'intro' => $t('Bâtissez vos fondations de sécurité dès le premier jour, sans ralentir votre équipe produit.', 'Build security foundations while your product and team grow.'),
            'panel' => $t('Nouveaux actifs', 'New assets'),
            'rows' => [
                ['critical', 'staging.acme.io', '8080', $t('Exposé', 'Exposed')],
                ['medium', 'api.acme.io', '443', $t('À vérifier', 'To review')],
                ['low', 'app.acme.io', '443', 'OK'],
            ],
            'features' => [
                [$t('Déploiement instantané', 'Fast setup'), $t('Activez votre surveillance en quelques minutes sans projet lourd.', 'Start monitoring without a heavy project.')],
                [$t('Contrôle de l’exposition', 'External exposure'), $t('Suivez l’évolution de votre surface d’attaque à chaque mise en production.', 'Track new public assets as the product grows.')],
                [$t('Protection des accès', 'Credential risk'), $t('Sécurisez les comptes de votre équipe contre les fuites de données.', 'Detect leaked startup accounts.')],
                [$t('Preuves de conformité', 'Audit readiness'), $t('Préparez vos preuves de sécurité pour rassurer vos clients et investisseurs.', 'Prepare evidence for customers and partners.')],
            ],
            'solutions' => ['attack-surface', 'credential-monitoring', 'cyberbuddy'],
        ],
        'it-teams' => [
            'title' => [$t('Maîtrisez vos priorités de', 'Know what to fix'), $t('sécurité.', 'first.')],
            'intro' => $t('Fournissez à vos équipes IT une liste d’actions précise et priorisée pour réduire les risques.', 'Give IT teams a direct security worklist.'),
            'panel' => $t('À corriger', 'To fix'),
            'rows' => [
                ['critical', $t('Restreindre le portail d’administration', 'Restrict the admin portal'), 'admin.acme.fr', $t('Critique', 'Critical')],
                ['high', $t('Mettre à jour le serveur web', 'Update the web server'), 'www.acme.fr', $t('Élevé', 'High')],
                ['medium', $t('Renouveler le certificat TLS', 'Renew the TLS certificate'), 'mail.acme.fr', $t('Moyen', 'Medium')],
            ],
            'features' => [
                [$t('Vue centralisée', 'Unified findings'), $t('Regroupez tous vos signaux de risque externe dans une interface unique.', 'Keep external risk signals in one view.')],
                [$t('Actions priorisées', 'Prioritized tasks'), $t('Concentrez-vous sur les correctifs qui ont le plus d’impact sur votre sécurité.', 'Focus on the important fixes.')],
                [$t('Suivi de résolution', 'Progress'), $t('Mesurez l’efficacité de vos remédiations au fil du temps.', 'Track remediation over time.')],
                [$t('Collaboration fluide', 'Shared ownership'), $t('Assignez clairement les responsabilités pour chaque action corrective.', 'Clarify who must fix each issue.')],
            ],
            'solutions' => ['attack-surface', 'vulnerability-management', 'cyberbuddy'],
        ],
        'cisos' => [
            'title' => [$t('Prenez des décisions basées sur des', 'Turn security signals into'), $t('données claires.', 'decisions.')],
            'intro' => $t('Bénéficiez d’une vision stratégique sur votre exposition, vos priorités et vos progrès réels.', 'Give security leaders a clear view of exposure, priority and progress.'),
            'panel' => $t('Programme', 'Program'),
            'rows' => [
                ['critical', $t('Exposition externe', 'External exposure'), $t('2 critiques', '2 critical'), $t('À traiter', 'Open')],
                ['medium', $t('Vulnérabilités', 'Vulnerabilities'), $t('7 moyennes', '7 medium'), $t('En cours', 'In progress')],
            ],
            'features' => [
                [$t('Tableau de bord stratégique', 'Executive visibility'), $t('Pilotez votre posture de sécurité avec des indicateurs de performance clairs.', 'Track posture with understandable indicators.')],
                [$t('Mesure de l’efficacité', 'Program progress'), $t('Démontrez la réduction des risques et l’impact de vos investissements.', 'Show remediation trends over time.')],
                [$t('Génération de preuves', 'Evidence'), $t('Automatisez la collecte de données structurées pour vos audits et revues.', 'Use structured data for reviews and audits.')],
            ],
            'solutions' => ['vulnerability-management', 'cyberbuddy', 'pentest'],
        ],
        'msps' => [
            'title' => [$t('Protégez vos clients plus', 'Protect more customers with less'), $t('efficacement.', 'friction.')],
            'intro' => $t('Standardisez votre offre de visibilité sécurité et guidez vos clients avec des conseils experts.', 'Standardize visibility and security guidance across customer environments.'),
            'panel' => $t('Clients', 'Customers'),
            'rows' => [
                ['critical', 'acme-retail.fr', $t('3 risques', '3 risks'), $t('Critique', 'Critical')],
                ['medium', 'acme-btp.fr', $t('1 risque', '1 risk'), $t('Moyen', 'Medium')],
                ['low', 'acme-sante.fr', $t('0 risque', '0 risks'), 'OK'],
            ],
            'features' => [
                [$t('Gestion multi-clients', 'Multi-customer view'), $t('Supervisez tous vos périmètres clients depuis une console unique.', 'Keep customer scopes organized.')],
                [$t('Processus reproductibles', 'Repeatable workflows'), $t('Appliquez une méthodologie de gestion des risques uniforme sur tous vos comptes.', 'Use the same risk process across accounts.')],
                [$t('Reporting professionnel', 'Clear reporting'), $t('Valorisez votre service avec des bilans de sécurité clairs et actionnables.', 'Provide simple customer-facing security updates.')],
                [$t('Nouvelles opportunités', 'Service extension'), $t('Enrichissez votre offre de services managés avec une brique de sécurité visible.', 'Add security visibility to managed IT services.')],
            ],
            'solutions' => ['attack-surface', 'vulnerability-management', 'credential-monitoring'],
        ],
    ];
    $page = $pages[$audience];
    $related = array_map(fn (string $slug) => [$slug, ...$solutions[$slug]], $page['solutions']);
@endphp

<main>
    {{-- Hero: title with accent word, next to an app-like panel (example data) --}}
    <section class="{{ $wrap }} ui:grid ui:grid-cols-1 ui:items-end ui:gap-12 ui:pb-22 ui:pt-16 ui:lg:grid-cols-2">
        <div>
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">{{ $page['title'][0] }} <span class="ui:text-brand-500">{{ $page['title'][1] }}</span></x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">{{ $page['intro'] }}</p>
            <div class="ui:mt-8 ui:flex ui:flex-wrap ui:gap-3">
                <x-site.button :href="route('register')">{{ $t('Commencer →', 'Start protecting →') }}</x-site.button>
                <x-site.button variant="outline" :href="route($routePrefix . 'solutions.index')">{{ $t('Découvrir Cywise', 'Explore Cywise') }}</x-site.button>
            </div>
        </div>

        <div class="ui:min-w-0 ui:overflow-hidden ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:font-sans ui:shadow-xs" aria-hidden="true">
            <div class="ui:border-0 ui:border-b ui:border-solid ui:border-line ui:px-5 ui:py-3.5 ui:font-mono ui:text-xs ui:uppercase ui:tracking-wider ui:text-slate-500">{{ $page['panel'] }}</div>
            <ul class="ui:m-0 ui:list-none ui:p-0">
                @foreach($page['rows'] as [$level, $text, $tag, $badge])
                    <li data-severity="{{ $level }}" class="ui:flex ui:items-center ui:gap-3 ui:px-5 ui:py-3.5 {{ $loop->first ? '' : 'ui:border-0 ui:border-t ui:border-solid ui:border-line' }}">
                        <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-1">
                            <span class="ui:text-[15px] ui:font-medium">{{ $text }}</span>
                            <x-ui.tag class="ui:self-start">{{ $tag }}</x-ui.tag>
                        </span>
                        <x-ui.badge :level="$level">{{ $badge }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- What you get --}}
    <section class="ui:border-0 ui:border-y ui:border-solid ui:border-line ui:bg-canvas">
        <div class="{{ $wrap }} ui:py-22">
            <x-site.display>{{ $t('Une sécurité qui reste claire.', 'Security that stays clear.') }}</x-site.display>
            <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-3 ui:sm:grid-cols-2">
                @foreach($page['features'] as [$title, $text])
                    <article class="ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-6">
                        <h3 class="ui:m-0 ui:text-2xl ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%">{{ $title }}</h3>
                        <p class="ui:m-0 ui:mt-3 ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Relevant solutions, each in its own tone --}}
    <section class="{{ $wrap }} ui:pt-24">
        <x-site.display>{!! $t('Une plateforme.<br><span class="ui:text-slate-400">Plusieurs défenses.</span>', 'One platform.<br><span class="ui:text-slate-400">Multiple defenses.</span>') !!}</x-site.display>
        <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-3">
            @foreach($related as [$slug, $tone, $name, $title, $text])
                <x-site.edge-card :tone="$tone" :href="route($routePrefix . 'solutions.' . $slug)">
                    <span class="ui:font-mono ui:text-[13px] ui:uppercase ui:text-slate-500">{{ $name }}</span>
                    <span class="ui:text-2xl ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%">{{ $title }}</span>
                    <span class="ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</span>
                    <span class="ui:mt-2 ui:text-sm ui:font-bold ui:uppercase ui:group-hover:text-brand-700">{{ $t('Découvrir →', 'Explore →') }}</span>
                </x-site.edge-card>
            @endforeach
        </div>
    </section>

    <x-site.steps
        :steps="[
            ['title' => $t('Analysez votre périmètre.', 'Connect your scope.'), 'text' => $t('Identifiez les actifs que Cywise doit surveiller pour votre organisation.', 'Define what Cywise must monitor for your organization.')],
            ['title' => $t('Ciblez les priorités.', 'Find the important risks.'), 'text' => $t('Cywise isole les risques critiques qui nécessitent votre attention immédiate.', 'Cywise groups signals and highlights the findings that need action.')],
            ['title' => $t('Sécurisez vos systèmes.', 'Fix with clear guidance.'), 'text' => $t('Votre équipe applique nos recommandations concrètes pour éliminer les risques.', 'Your team gets direct actions without unnecessary security complexity.')],
        ]"
    >
        <x-slot:title>{!! $t('Découvrez.<br>Priorisez.<br><span class="ui:text-brand-500">Sécurisez.</span>', 'See.<br>Prioritize.<br><span class="ui:text-brand-500">Act.</span>') !!}</x-slot:title>
    </x-site.steps>

    <x-site.cta :href="route('register')" :label="$t('Démarrer maintenant →', 'Start protecting →')">
        <x-slot:title>{!! $t('Démystifiez votre<br>cybersécurité.', 'Make your<br>security visible.') !!}</x-slot:title>
    </x-site.cta>
</main>
