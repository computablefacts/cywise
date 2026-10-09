@php
    // Audience page, shared by pages/for-whom/* (fr) and pages/en/for-whom/* (en).
    // $audience: smbs | startups | it-teams | cisos | msps
    $isEnglish = $locale === 'en';
    $t = fn (string $fr, string $en) => $isEnglish ? $en : $fr;
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
    $wrap = 'ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:sm:px-6';

    // Same catalogue as the home page; tones fixed per solution (see site/edge-card)
    $solutions = [
        'attack-surface' => ['critical', $t('Surface d’attaque', 'Attack surface'), $t('Voyez ce que les attaquants voient.', 'See what attackers see.'), $t('Surveillez les domaines publics, serveurs, ports et services exposés.', 'Monitor public domains, servers, ports and exposed services.')],
        'vulnerability-management' => ['medium', $t('Vulnérabilités', 'Vulnerabilities'), $t('Trouvez les points faibles.', 'Find the weak points.'), $t('Identifiez les vulnérabilités et concentrez-vous sur les risques les plus importants.', 'Identify vulnerabilities and focus on the most important risks.')],
        'credential-monitoring' => ['low', $t('Identifiants', 'Credentials'), $t('Sachez ce qui a fuité.', 'Know what leaked.'), $t('Détectez les identifiants compromis liés à votre entreprise.', 'Detect compromised credentials linked to your company.')],
        'cyberbuddy' => ['brand', 'CyberBuddy', $t('Demandez. Comprenez. Agissez.', 'Ask. Understand. Act.'), $t('Obtenez des conseils clairs adaptés au contexte de votre entreprise.', 'Get clear cybersecurity guidance from your company context.')],
        'pssi' => ['info', 'PSSI', $t('Créez votre politique de sécurité.', 'Build your security policy.'), $t('Créez une politique de sécurité pratique pour votre organisation.', 'Create a practical security policy for your organization.')],
        'pentest' => ['ink', 'Pentest', $t('Faites tester par des experts.', 'Put humans on the attack.'), $t('Faites tester vos applications critiques par des experts en sécurité.', 'Test critical applications with experienced security experts.')],
    ];

    // Per audience: title [lead, accent], intro, hero rows [level, text, tag, badge] (example data), features, solutions
    $pages = [
        'smbs' => [
            'title' => [$t('La sécurité sans une grande équipe', 'Security without a large'), $t('dédiée.', 'security team.')],
            'intro' => $t('Donnez aux PME une visibilité claire et des actions concrètes.', 'Give small and medium businesses clear visibility and practical actions.'),
            'panel' => 'acme.fr',
            'rows' => [
                ['critical', $t('Portail d’administration exposé', 'Exposed admin portal'), 'admin.acme.fr', $t('Critique', 'Critical')],
                ['high', $t('Identifiant compromis', 'Leaked credential'), 'compta@acme.fr', $t('Élevé', 'High')],
                ['medium', $t('Configuration TLS', 'TLS configuration'), 'www.acme.fr', $t('Moyen', 'Medium')],
            ],
            'features' => [
                [$t('Visibilité simple', 'Simple visibility'), $t('Voyez les expositions importantes sans outils spécialisés.', 'See important exposure without specialist tools.')],
                [$t('Corrections guidées', 'Guided fixes'), $t('Transformez les constats techniques en actions directes.', 'Turn technical findings into direct actions.')],
                [$t('Reporting clair', 'Clear reporting'), $t('Partagez l’état de sécurité avec la direction.', 'Share security status with management.')],
                [$t('Protection continue', 'Continuous protection'), $t('Continuez la surveillance après la première évaluation.', 'Keep monitoring after the first assessment.')],
            ],
            'solutions' => ['attack-surface', 'credential-monitoring', 'cyberbuddy'],
        ],
        'startups' => [
            'title' => [$t('Avancez vite sans négliger la', 'Move fast without ignoring'), $t('sécurité.', 'security.')],
            'intro' => $t('Construisez vos bases de sécurité pendant la croissance du produit et de l’équipe.', 'Build security foundations while your product and team grow.'),
            'panel' => $t('Nouveaux actifs', 'New assets'),
            'rows' => [
                ['critical', 'staging.acme.io', '8080', $t('Exposé', 'Exposed')],
                ['medium', 'api.acme.io', '443', $t('À vérifier', 'To review')],
                ['low', 'app.acme.io', '443', 'OK'],
            ],
            'features' => [
                [$t('Mise en place rapide', 'Fast setup'), $t('Commencez la surveillance sans projet lourd.', 'Start monitoring without a heavy project.')],
                [$t('Exposition externe', 'External exposure'), $t('Suivez les nouveaux actifs publics pendant la croissance du produit.', 'Track new public assets as the product grows.')],
                [$t('Risque identifiants', 'Credential risk'), $t('Détectez les comptes de startup compromis.', 'Detect leaked startup accounts.')],
                [$t('Préparation aux audits', 'Audit readiness'), $t('Préparez des preuves pour les clients et partenaires.', 'Prepare evidence for customers and partners.')],
            ],
            'solutions' => ['attack-surface', 'credential-monitoring', 'pssi'],
        ],
        'it-teams' => [
            'title' => [$t('Sachez quoi corriger en', 'Know what to fix'), $t('premier.', 'first.')],
            'intro' => $t('Donnez aux équipes IT une liste d’actions de sécurité claire.', 'Give IT teams a direct security worklist.'),
            'panel' => $t('À corriger', 'To fix'),
            'rows' => [
                ['critical', $t('Restreindre le portail d’administration', 'Restrict the admin portal'), 'admin.acme.fr', $t('Critique', 'Critical')],
                ['high', $t('Mettre à jour le serveur web', 'Update the web server'), 'www.acme.fr', $t('Élevé', 'High')],
                ['medium', $t('Renouveler le certificat TLS', 'Renew the TLS certificate'), 'mail.acme.fr', $t('Moyen', 'Medium')],
            ],
            'features' => [
                [$t('Constats centralisés', 'Unified findings'), $t('Regroupez les signaux de risque externe dans une seule vue.', 'Keep external risk signals in one view.')],
                [$t('Tâches priorisées', 'Prioritized tasks'), $t('Concentrez-vous sur les corrections importantes.', 'Focus on the important fixes.')],
                [$t('Progrès', 'Progress'), $t('Suivez la remédiation dans le temps.', 'Track remediation over time.')],
                [$t('Responsabilité partagée', 'Shared ownership'), $t('Clarifiez qui doit corriger chaque problème.', 'Clarify who must fix each issue.')],
            ],
            'solutions' => ['attack-surface', 'vulnerability-management', 'cyberbuddy'],
        ],
        'cisos' => [
            'title' => [$t('Transformez les signaux de sécurité en', 'Turn security signals into'), $t('décisions.', 'decisions.')],
            'intro' => $t('Donnez aux responsables sécurité une vue claire de l’exposition, des priorités et des progrès.', 'Give security leaders a clear view of exposure, priority and progress.'),
            'panel' => $t('Programme', 'Program'),
            'rows' => [
                ['critical', $t('Exposition externe', 'External exposure'), $t('2 critiques', '2 critical'), $t('À traiter', 'Open')],
                ['medium', $t('Vulnérabilités', 'Vulnerabilities'), $t('7 moyennes', '7 medium'), $t('En cours', 'In progress')],
                ['low', $t('Politique de sécurité', 'Security policy'), 'PSSI', $t('À jour', 'Up to date')],
            ],
            'features' => [
                [$t('Visibilité direction', 'Executive visibility'), $t('Suivez la posture avec des indicateurs compréhensibles.', 'Track posture with understandable indicators.')],
                [$t('Progrès du programme', 'Program progress'), $t('Montrez l’évolution de la remédiation.', 'Show remediation trends over time.')],
                [$t('Accompagnement des politiques', 'Policy support'), $t('Maintenez une gouvernance sécurité pratique.', 'Maintain practical security governance.')],
                [$t('Preuves', 'Evidence'), $t('Utilisez des données structurées pour les revues et audits.', 'Use structured data for reviews and audits.')],
            ],
            'solutions' => ['vulnerability-management', 'pssi', 'pentest'],
        ],
        'msps' => [
            'title' => [$t('Protégez plus de clients, plus', 'Protect more customers with less'), $t('simplement.', 'friction.')],
            'intro' => $t('Standardisez la visibilité et les conseils sécurité sur les environnements clients.', 'Standardize visibility and security guidance across customer environments.'),
            'panel' => $t('Clients', 'Customers'),
            'rows' => [
                ['critical', 'acme-retail.fr', $t('3 risques', '3 risks'), $t('Critique', 'Critical')],
                ['medium', 'acme-btp.fr', $t('1 risque', '1 risk'), $t('Moyen', 'Medium')],
                ['low', 'acme-sante.fr', $t('0 risque', '0 risks'), 'OK'],
            ],
            'features' => [
                [$t('Vue multi-clients', 'Multi-customer view'), $t('Gardez les périmètres clients bien organisés.', 'Keep customer scopes organized.')],
                [$t('Flux répétables', 'Repeatable workflows'), $t('Appliquez le même processus de gestion des risques à tous les comptes.', 'Use the same risk process across accounts.')],
                [$t('Reporting clair', 'Clear reporting'), $t('Fournissez à vos clients des bilans de sécurité clairs.', 'Provide simple customer-facing security updates.')],
                [$t('Extension de service', 'Service extension'), $t('Ajoutez une visibilité sécurité à vos services IT managés.', 'Add security visibility to managed IT services.')],
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
            ['title' => $t('Définissez votre périmètre.', 'Connect your scope.'), 'text' => $t('Définissez ce que Cywise doit surveiller pour votre organisation.', 'Define what Cywise must monitor for your organization.')],
            ['title' => $t('Trouvez les risques importants.', 'Find the important risks.'), 'text' => $t('Cywise regroupe les signaux et met en évidence les constats qui nécessitent une action.', 'Cywise groups signals and highlights the findings that need action.')],
            ['title' => $t('Corrigez avec des conseils clairs.', 'Fix with clear guidance.'), 'text' => $t('Votre équipe reçoit des actions concrètes, sans complexité inutile.', 'Your team gets direct actions without unnecessary security complexity.')],
        ]"
    >
        <x-slot:title>{!! $t('Voyez.<br>Priorisez.<br><span class="ui:text-brand-500">Agissez.</span>', 'See.<br>Prioritize.<br><span class="ui:text-brand-500">Act.</span>') !!}</x-slot:title>
    </x-site.steps>

    <x-site.cta :href="route('register')" :label="$t('Commencer →', 'Start protecting →')">
        <x-slot:title>{!! $t('Rendez votre<br>sécurité visible.', 'Make your<br>security visible.') !!}</x-slot:title>
    </x-site.cta>
</main>
