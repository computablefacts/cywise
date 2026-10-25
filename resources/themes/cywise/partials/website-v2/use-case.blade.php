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
        ['title' => $t('Analysez votre périmètre.', 'Connect your scope.'), 'text' => $t('Identifiez les actifs que Cywise doit surveiller pour votre organisation.', 'Define what Cywise must monitor for your organization.')],
        ['title' => $t('Ciblez les priorités.', 'Find the important risks.'), 'text' => $t('Cywise isole les risques critiques qui nécessitent votre attention immédiate.', 'Cywise groups signals and highlights the findings that need action.')],
        ['title' => $t('Sécurisez vos systèmes.', 'Fix with clear guidance.'), 'text' => $t('Votre équipe applique nos recommandations concrètes pour éliminer les risques.', 'Your team gets direct actions without unnecessary security complexity.')],
    ];

    // Hero accent = the matching solution's tone (see site/edge-card); rows = example data (acme.fr)
    $cases = [
        'find-vulnerabilities' => [
            'accent' => 'ui:text-medium',
            'title' => [$t('Trouvez vos failles avant', 'Find what can'), $t('les attaquants.', 'break.')],
            'lede' => $t('Détectez vos vulnérabilités et transformez-les en un plan d’action priorisé pour vos équipes.', 'Discover weaknesses and turn them into a prioritized remediation list.'),
            'panel' => $t('Vulnérabilités · acme.fr', 'Vulnerabilities · acme.fr'),
            'font' => $sans,
            'rows' => [
                ['critical', $t('Portail d’administration exposé', 'Exposed admin portal'), 'admin.acme.fr', $levels['critical']],
                ['high', $t('Composant web obsolète', 'Outdated web component'), 'www.acme.fr', $levels['high']],
                ['medium', $t('Configuration TLS', 'TLS configuration'), 'mail.acme.fr', $levels['medium']],
                ['low', $t('En-tête de sécurité manquant', 'Missing security header'), 'blog.acme.fr', $levels['low']],
            ],
            'features' => [
                [$t('Scan d’exposition', 'Scan exposure'), $t('Analysez vos systèmes accessibles depuis Internet en continu.', 'Review internet-facing systems.')],
                [$t('Détection des failles', 'Identify weaknesses'), $t('Identifiez les composants et les configurations vulnérables de votre infrastructure.', 'Find known vulnerable components and configurations.')],
                [$t('Ciblage des urgences', 'Prioritize'), $t('Concentrez vos efforts sur les constats à fort impact pour votre entreprise.', 'Separate urgent issues from low-impact findings.')],
                [$t('Maîtrise du risque', 'Explain risk'), $t('Comprenez précisément pourquoi chaque vulnérabilité doit être traitée.', 'Understand why each issue matters.')],
                [$t('Pilotage des actions', 'Assign action'), $t('Attribuez chaque correction au responsable adéquat dans votre organisation.', 'Give the right fix to the right owner.')],
                [$t('Validation des progrès', 'Track closure'), $t('Mesurez l’amélioration de votre posture de sécurité dans le temps.', 'Confirm progress over time.')],
            ],
            'steps' => $monitoring,
        ],
        'monitor-attack-surface' => [
            'accent' => 'ui:text-critical',
            'title' => [$t('Maîtrisez votre', 'See your'), $t('empreinte externe.', 'external footprint.')],
            'lede' => $t('Gardez le contrôle sur vos actifs publics et vos services exposés en temps réel.', 'Keep track of public assets and exposed services as they change.'),
            'panel' => $t('Changements · acme.fr', 'Changes · acme.fr'),
            'font' => $mono,
            'rows' => [
                ['critical', 'staging.acme.fr', '8080', $t('Nouveau', 'New')],
                ['medium', 'ftp.acme.fr', '21', $t('Nouveau', 'New')],
                ['low', 'www.acme.fr', '443', $t('Inchangé', 'Unchanged')],
                ['info', 'old.acme.fr', '443', $t('Retiré', 'Removed')],
            ],
            'features' => [
                [$t('Inventaire automatique', 'Discover assets'), $t('Référencez vos domaines, hôtes et services publics en continu.', 'Find domains, hosts and public services.')],
                [$t('Alerte de changement', 'Detect changes'), $t('Soyez notifié immédiatement dès l’apparition d’une nouvelle exposition.', 'See when new exposure appears.')],
                [$t('Contrôle de l’ombre', 'Reduce unknowns'), $t('Repérez vos actifs oubliés, obsolètes ou non gérés.', 'Find forgotten or unmanaged assets.')],
                [$t('Vigilance accrue', 'Prioritize exposure'), $t('Concentrez votre attention sur les nouveaux services exposés à risque.', 'Focus on risky public services.')],
                [$t('Historique d’évolution', 'Keep history'), $t('Visualisez comment votre surface d’attaque évolue au fil du temps.', 'Track how your footprint evolves.')],
            ],
            'steps' => $monitoring,
        ],
        'check-leaked-credentials' => [
            'accent' => 'ui:text-low',
            'title' => [$t('Protégez vos', 'Find'), $t('comptes compromis.', 'compromised accounts.')],
            'lede' => $t('Identifiez vos identifiants exposés et prévenez les risques de prise de contrôle de compte.', 'Detect exposed credentials and reduce account takeover risk.'),
            'panel' => $t('Identifiants · acme.fr', 'Credentials · acme.fr'),
            'font' => $mono,
            'rows' => [
                ['critical', 'j.martin@acme.fr', 'Admin', $t('Compromis', 'Compromised')],
                ['medium', 'compta@acme.fr', $t('Partagé', 'Shared'), $t('À changer', 'To change')],
                ['low', 's.durand@acme.fr', $t('Utilisateur', 'User'), $t('Réinitialisé', 'Reset')],
                ['low', 'l.bernard@acme.fr', $t('Utilisateur', 'User'), $t('Réinitialisé', 'Reset')],
            ],
            'features' => [
                [$t('Surveillance active', 'Domain monitoring'), $t('Scannez les fuites de données pour protéger vos domaines d’entreprise.', 'Watch identities linked to company domains.')],
                [$t('Détection préventive', 'Leak signals'), $t('Repérez vos identifiants dans les bases de données de compromissions tierces.', 'Find credentials present in known leak sources.')],
                [$t('Remédiation assistée', 'User action'), $t('Guidez vos collaborateurs vers les actions de sécurisation nécessaires.', 'Tell users what to change.')],
                [$t('Revue de sécurité', 'Access review'), $t('Vérifiez l’intégrité de vos comptes sensibles après chaque alerte.', 'Review sensitive accounts after a leak.')],
                [$t('Vigilance permanente', 'Ongoing checks'), $t('Maintenez un contrôle continu sur l’exposition de vos identifiants.', 'Continue detection after cleanup.')],
            ],
            'steps' => $monitoring,
        ],
        'prepare-audit' => [
            'accent' => 'ui:text-brand-500',
            'title' => [$t('Transformez votre sécurité en', 'Turn security work into'), $t('preuves.', 'evidence.')],
            'lede' => $t('Centralisez vos preuves, vos politiques et vos actions avant vos audits et revues de conformité.', 'Organize findings, policies and progress before an audit or customer review.'),
            'panel' => $t('Audit · Acme', 'Audit · Acme'),
            'font' => $sans,
            'rows' => [
                ['critical', $t('Sauvegardes hors ligne', 'Offline backups'), $t('Contrôle', 'Control'), $t('Manquant', 'Missing')],
                ['medium', $t('Écarts ouverts', 'Open gaps'), '3', $t('En cours', 'In progress')],
                ['low', $t('Actions de remédiation', 'Remediation actions'), '12', $t('Suivi', 'Tracked')],
            ],
            'features' => [
                [$t('Diagnostic des écarts', 'Gap visibility'), $t('Identifiez vos contrôles manquants et vos risques non couverts.', 'See missing controls and open risks.')],
                [$t('Conformité documentaire', 'Policy readiness'), $t('Préparez et validez vos documents de sécurité pour vos auditeurs.', 'Review security documents.')],
                [$t('Justification des progrès', 'Remediation evidence'), $t('Démontrez vos actions correctives et l’évolution de votre posture.', 'Show actions and progress.')],
                [$t('Pilotage stratégique', 'Management view'), $t('Synthétisez votre situation réelle pour vos instances de décision.', 'Summarize current posture.')],
                [$t('Veille post-audit', 'Follow-up'), $t('Conservez une visibilité sur vos engagements après l’audit.', 'Keep work visible after the audit.')],
            ],
            'steps' => null,
        ],
        'run-pentest' => [
            'accent' => 'ui:text-brand-500',
            'title' => [$t('Testez vos défenses avant que', 'Test before'), $t('l’attaque n’ait lieu.', 'attackers do.')],
            'lede' => $t('Faites évaluer vos applications et infrastructures critiques par nos experts en sécurité offensive.', 'Use expert testing to challenge critical applications and infrastructure.'),
            'panel' => $t('Rapport de pentest · app.acme.fr', 'Pentest report · app.acme.fr'),
            'font' => $sans,
            'rows' => [
                ['critical', $t('Injection SQL', 'SQL injection'), $t('Corrigé', 'Fixed'), $levels['critical']],
                ['high', $t('Contrôle d’accès défaillant', 'Broken access control'), $t('Corrigé', 'Fixed'), $levels['high']],
                ['medium', $t('Session sans expiration', 'Session never expires'), $t('Ouvert', 'Open'), $levels['medium']],
                ['low', $t('Divulgation de version', 'Version disclosure'), $t('Ouvert', 'Open'), $levels['low']],
            ],
            'features' => [
                [$t('Définition du cadre', 'Scope'), $t('Ciblez précisément vos systèmes et vos objectifs de sécurité.', 'Define systems and goals.')],
                [$t('Reconnaissance experte', 'Reconnaissance'), $t('Analysez votre surface d’attaque avec une approche offensive.', 'Review the attack surface.')],
                [$t('Simulations d’attaque', 'Manual testing'), $t('Éprouvez vos systèmes grâce aux techniques de nos pentesters experts.', 'Challenge systems with expert techniques.')],
                [$t('Documentation des failles', 'Evidence'), $t('Obtenez des preuves concrètes pour chaque vulnérabilité confirmée.', 'Document each confirmed weakness.')],
                [$t('Rapport priorisé', 'Prioritized report'), $t('Recevez vos résultats classés par sévérité pour une action immédiate.', 'Order findings by severity.')],
                [$t('Validation des correctifs', 'Retest'), $t('Confirmez la résolution de vos failles critiques après remédiation.', 'Verify important remediation.')],
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

    <x-site.cta :href="route('register')" :label="$t('Démarrer maintenant →', 'Start protecting →')">
        <x-slot:title>{!! $t('Démystifiez votre<br>cybersécurité.', 'Make your<br>security visible.') !!}</x-slot:title>
    </x-site.cta>
</main>
