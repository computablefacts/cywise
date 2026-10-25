@php
    // Use-case page, shared by pages/use-cases/* (fr) and pages/en/use-cases/* (en). $case = page slug.
    $isEnglish = $locale === 'en';
    $t = fn (string $fr, string $en) => $isEnglish ? $en : $fr;
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
    $wrap = 'ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:sm:px-6';

    // Row text in the app replica: hosts and e-mails in mono, findings in sans
    $mono = 'ui:font-mono ui:text-sm';
    $sans = 'ui:text-[15px] ui:font-medium';

    $levels = [
        'critical' => $t('Critique', 'Critical'),
        'high' => $t('Élevé', 'High'),
        'medium' => $t('Moyen', 'Medium'),
        'low' => $t('Faible', 'Low'),
    ];

    // Generic "see, prioritize, act" flow: only fits the monitoring use cases
    $monitoring = [
        ['title' => $t('Analysez votre périmètre.', 'Analyze your scope.'), 'text' => $t('Identifiez les actifs que Cywise doit surveiller pour votre organisation.', 'Identify the assets Cywise should monitor for your organization.')],
        ['title' => $t('Ciblez les priorités.', 'Target priorities.'), 'text' => $t('Cywise isole les risques critiques qui nécessitent votre attention immédiate.', 'Cywise isolates critical risks that require your immediate attention.')],
        ['title' => $t('Sécurisez vos systèmes.', 'Secure your systems.'), 'text' => $t('Votre équipe applique nos recommandations concrètes pour éliminer les risques.', 'Your team applies our concrete recommendations to eliminate risks.')],
    ];

    // Hero accent = the matching solution's tone (see site/edge-card); rows = example data (acme.fr)
    $cases = [
        'find-vulnerabilities' => [
            'accent' => 'ui:text-medium',
            'title' => [$t('Trouvez vos failles avant', 'Find your flaws before'), $t('les attaquants.', 'attackers do.')],
            'lede' => $t('Détectez vos vulnérabilités et transformez-les en un plan d’action priorisé pour vos équipes.', 'Detect your vulnerabilities and turn them into a prioritized action plan for your teams.'),
            'panel' => $t('Vulnérabilités · acme.fr', 'Vulnerabilities · acme.fr'),
            'font' => $sans,
            'rows' => [
                ['critical', $t('Portail d’administration exposé', 'Exposed admin portal'), 'admin.acme.fr', $levels['critical']],
                ['high', $t('Composant web obsolète', 'Outdated web component'), 'www.acme.fr', $levels['high']],
                ['medium', $t('Configuration TLS', 'TLS configuration'), 'mail.acme.fr', $levels['medium']],
                ['low', $t('En-tête de sécurité manquant', 'Missing security header'), 'blog.acme.fr', $levels['low']],
            ],
            'features' => [
                [$t('Scan d’exposition', 'Exposure scan'), $t('Analysez vos systèmes accessibles depuis Internet en continu.', 'Continuously analyze your systems accessible from the Internet.')],
                [$t('Détection des failles', 'Flaw detection'), $t('Identifiez les composants et les configurations vulnérables de votre infrastructure.', 'Identify vulnerable components and configurations of your infrastructure.')],
                [$t('Ciblage des urgences', 'Emergency targeting'), $t('Concentrez vos efforts sur les constats à fort impact pour votre entreprise.', 'Focus your efforts on high-impact findings for your company.')],
                [$t('Maîtrise du risque', 'Risk control'), $t('Comprenez précisément pourquoi chaque vulnérabilité doit être traitée.', 'Understand exactly why each vulnerability must be addressed.')],
                [$t('Pilotage des actions', 'Action steering'), $t('Attribuez chaque correction au responsable adéquat dans votre organisation.', 'Assign each fix to the appropriate owner in your organization.')],
                [$t('Validation des progrès', 'Progress validation'), $t('Mesurez l’amélioration de votre posture de sécurité dans le temps.', 'Measure the improvement of your security posture over time.')],
            ],
            'steps' => $monitoring,
        ],
        'monitor-attack-surface' => [
            'accent' => 'ui:text-critical',
            'title' => [$t('Maîtrisez votre', 'Master your'), $t('empreinte externe.', 'external footprint.')],
            'lede' => $t('Gardez le contrôle sur vos actifs publics et vos services exposés en temps réel.', 'Maintain control over your public assets and exposed services in real-time.'),
            'panel' => $t('Changements · acme.fr', 'Changes · acme.fr'),
            'font' => $mono,
            'rows' => [
                ['critical', 'staging.acme.fr', '8080', $t('Nouveau', 'New')],
                ['medium', 'ftp.acme.fr', '21', $t('Nouveau', 'New')],
                ['low', 'www.acme.fr', '443', $t('Inchangé', 'Unchanged')],
                ['info', 'old.acme.fr', '443', $t('Retiré', 'Removed')],
            ],
            'features' => [
                [$t('Inventaire automatique', 'Automatic inventory'), $t('Référencez vos domaines, hôtes et services publics en continu.', 'List your domains, hosts and public services continuously.')],
                [$t('Alerte de changement', 'Change alert'), $t('Soyez notifié immédiatement dès l’apparition d’une nouvelle exposition.', 'Be notified immediately as soon as a new exposure appears.')],
                [$t('Contrôle de l’ombre', 'Shadow IT control'), $t('Repérez vos actifs oubliés, obsolètes ou non gérés.', 'Identify your forgotten, obsolete or unmanaged assets.')],
                [$t('Vigilance accrue', 'Increased vigilance'), $t('Concentrez votre attention sur les nouveaux services exposés à risque.', 'Focus your attention on new risky exposed services.')],
                [$t('Historique d’évolution', 'Evolution history'), $t('Visualisez comment votre surface d’attaque évolue au fil du temps.', 'Visualize how your attack surface evolves over time.')],
            ],
            'steps' => $monitoring,
        ],
        'check-leaked-credentials' => [
            'accent' => 'ui:text-low',
            'title' => [$t('Protégez vos', 'Protect your'), $t('comptes compromis.', 'compromised accounts.')],
            'lede' => $t('Identifiez vos identifiants exposés et prévenez les risques de prise de contrôle de compte.', 'Identify your exposed credentials and prevent account takeover risks.'),
            'panel' => $t('Identifiants · acme.fr', 'Credentials · acme.fr'),
            'font' => $mono,
            'rows' => [
                ['critical', 'j.martin@acme.fr', 'Admin', $t('Compromis', 'Compromised')],
                ['medium', 'compta@acme.fr', $t('Partagé', 'Shared'), $t('À changer', 'To change')],
                ['low', 's.durand@acme.fr', $t('Utilisateur', 'User'), $t('Réinitialisé', 'Reset')],
                ['low', 'l.bernard@acme.fr', $t('Utilisateur', 'User'), $t('Réinitialisé', 'Reset')],
            ],
            'features' => [
                [$t('Surveillance active', 'Active monitoring'), $t('Scannez les fuites de données pour protéger vos domaines d’entreprise.', 'Scan data leaks to protect your company domains.')],
                [$t('Détection préventive', 'Preventive detection'), $t('Repérez vos identifiants dans les bases de données de compromissions tierces.', 'Identify your credentials in third-party breach databases.')],
                [$t('Remédiation assistée', 'Assisted remediation'), $t('Guidez vos collaborateurs vers les actions de sécurisation nécessaires.', 'Guide your employees towards necessary security actions.')],
                [$t('Revue de sécurité', 'Security review'), $t('Vérifiez l’intégrité de vos comptes sensibles après chaque alerte.', 'Verify the integrity of your sensitive accounts after each alert.')],
                [$t('Vigilance permanente', 'Permanent vigilance'), $t('Maintenez un contrôle continu sur l’exposition de vos identifiants.', 'Maintain continuous control over the exposure of your credentials.')],
            ],
            'steps' => $monitoring,
        ],
        'prepare-audit' => [
            'accent' => 'ui:text-brand-500',
            'title' => [$t('Transformez votre sécurité en', 'Turn your security into'), $t('preuves.', 'evidence.')],
            'lede' => $t('Centralisez vos preuves, vos politiques et vos actions avant vos audits et revues de conformité.', 'Centralize your evidence, policies and actions before your audits and compliance reviews.'),
            'panel' => $t('Audit · Acme', 'Audit · Acme'),
            'font' => $sans,
            'rows' => [
                ['critical', $t('Sauvegardes hors ligne', 'Offline backups'), $t('Contrôle', 'Control'), $t('Manquant', 'Missing')],
                ['medium', $t('Écarts ouverts', 'Open gaps'), '3', $t('En cours', 'In progress')],
                ['low', $t('Actions de remédiation', 'Remediation actions'), '12', $t('Suivi', 'Tracked')],
            ],
            'features' => [
                [$t('Diagnostic des écarts', 'Gap diagnosis'), $t('Identifiez vos contrôles manquants et vos risques non couverts.', 'Identify your missing controls and uncovered risks.')],
                [$t('Conformité documentaire', 'Documentary compliance'), $t('Préparez et validez vos documents de sécurité pour vos auditeurs.', 'Prepare and validate your security documents for your auditors.')],
                [$t('Justification des progrès', 'Progress justification'), $t('Démontrez vos actions correctives et l’évolution de votre posture.', 'Demonstrate your corrective actions and the evolution of your posture.')],
                [$t('Pilotage stratégique', 'Strategic steering'), $t('Synthétisez votre situation réelle pour vos instances de décision.', 'Summarize your real situation for your decision-making bodies.')],
                [$t('Veille post-audit', 'Post-audit watch'), $t('Conservez une visibilité sur vos engagements après l’audit.', 'Maintain visibility on your commitments after the audit.')],
            ],
            'steps' => null,
        ],
        'run-pentest' => [
            'accent' => 'ui:text-brand-500',
            'title' => [$t('Testez vos défenses avant que', 'Test your defenses before'), $t('l’attaque n’ait lieu.', 'the attack occurs.')],
            'lede' => $t('Faites évaluer vos applications et infrastructures critiques par nos experts en sécurité offensive.', 'Have your critical applications and infrastructures evaluated by our offensive security experts.'),
            'panel' => $t('Rapport de pentest · app.acme.fr', 'Pentest report · app.acme.fr'),
            'font' => $sans,
            'rows' => [
                ['critical', $t('Injection SQL', 'SQL injection'), $t('Corrigé', 'Fixed'), $levels['critical']],
                ['high', $t('Contrôle d’accès défaillant', 'Broken access control'), $t('Corrigé', 'Fixed'), $levels['high']],
                ['medium', $t('Session sans expiration', 'Session never expires'), $t('Ouvert', 'Open'), $levels['medium']],
                ['low', $t('Divulgation de version', 'Version disclosure'), $t('Ouvert', 'Open'), $levels['low']],
            ],
            'features' => [
                [$t('Définition du cadre', 'Scoping'), $t('Ciblez précisément vos systèmes et vos objectifs de sécurité.', 'Precisely target your systems and security goals.')],
                [$t('Reconnaissance experte', 'Expert reconnaissance'), $t('Analysez votre surface d’attaque avec une approche offensive.', 'Analyze your attack surface with an offensive approach.')],
                [$t('Simulations d’attaque', 'Attack simulations'), $t('Éprouvez vos systèmes grâce aux techniques de nos pentesters experts.', 'Challenge your systems using the techniques of our expert pentesters.')],
                [$t('Documentation des failles', 'Flaw documentation'), $t('Obtenez des preuves concrètes pour chaque vulnérabilité confirmée.', 'Get concrete evidence for each confirmed vulnerability.')],
                [$t('Rapport priorisé', 'Prioritized report'), $t('Recevez vos résultats classés par sévérité pour une action immédiate.', 'Receive your results ranked by severity for immediate action.')],
                [$t('Validation des correctifs', 'Fix validation'), $t('Confirmez la résolution de vos failles critiques après remédiation.', 'Confirm the resolution of your critical flaws after remediation.')],
            ],
            'steps' => null,
        ],
    ];

    $useCase = $cases[$case];
@endphp

<main>
    {{-- Hero: title + app replica --}}
    <section class="{{ $wrap }} ui:grid ui:grid-cols-1 ui:items-end ui:gap-12 ui:pb-22 ui:pt-16 ui:lg:grid-cols-2">
        <div>
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">{{ $useCase['title'][0] }} <span class="{{ $useCase['accent'] }}">{{ $useCase['title'][1] }}</span></x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">{{ $useCase['lede'] }}</p>
            <div class="ui:mt-8 ui:flex ui:flex-wrap ui:gap-3">
                <x-site.button :href="route('register')">{{ $t('Commencer →', 'Start protecting →') }}</x-site.button>
                <x-site.button variant="outline" :href="route($routePrefix . 'solutions.index')">{{ $t('Découvrir Cywise', 'Explore Cywise') }}</x-site.button>
            </div>
        </div>

        {{-- Panel built like the app's severity rows (example data) --}}
        <div class="ui:min-w-0 ui:overflow-hidden ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:font-sans ui:shadow-xs" aria-hidden="true">
            <div class="ui:border-0 ui:border-b ui:border-solid ui:border-line ui:px-5 ui:py-3.5 ui:font-mono ui:text-xs ui:uppercase ui:tracking-wider ui:text-slate-500">{{ $useCase['panel'] }}</div>
            <ul class="ui:m-0 ui:list-none ui:p-0">
                @foreach($useCase['rows'] as [$level, $text, $tag, $state])
                    <li data-severity="{{ $level }}" class="ui:flex ui:items-center ui:gap-3 ui:px-5 ui:py-3.5 {{ $loop->first ? '' : 'ui:border-0 ui:border-t ui:border-solid ui:border-line' }}">
                        <span class="ui:min-w-0 ui:flex-1 ui:truncate {{ $useCase['font'] }}" title="{{ $text }}">{{ $text }}</span>
                        <x-ui.tag>{{ $tag }}</x-ui.tag>
                        <x-ui.badge :level="$level">{{ $state }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- What you get --}}
    <section class="ui:border-0 ui:border-y ui:border-solid ui:border-line ui:bg-canvas">
        <div class="{{ $wrap }} ui:py-22">
            <x-site.display>{{ $t('Une sécurité qui reste claire.', 'Security that stays clear.') }}</x-site.display>
            <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-3 ui:sm:grid-cols-2 ui:xl:grid-cols-3">
                @foreach($useCase['features'] as [$title, $text])
                    <article class="ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-6">
                        <h3 class="ui:m-0 ui:text-2xl ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%">{{ $title }}</h3>
                        <p class="ui:m-0 ui:mt-3 ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if($useCase['steps'])
        <x-site.steps :steps="$useCase['steps']">
            <x-slot:title>{!! $t('Voyez.<br>Priorisez.<br><span class="ui:text-brand-500">Agissez.</span>', 'See.<br>Prioritize.<br><span class="ui:text-brand-500">Act.</span>') !!}</x-slot:title>
        </x-site.steps>
    @else
        {{-- No steps: keep air between the features band and the CTA --}}
        <div class="ui:h-24" aria-hidden="true"></div>
    @endif

    <x-site.cta :href="route('register')" :label="$t('Démarrer maintenant →', 'Start now →')">
        <x-slot:title>{!! $t('Démystifiez votre<br>cybersécurité.', 'Demystify your<br>cybersecurity.') !!}</x-slot:title>
    </x-site.cta>
</main>
