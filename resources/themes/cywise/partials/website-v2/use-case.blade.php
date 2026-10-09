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
        ['title' => $t('Définissez votre périmètre.', 'Connect your scope.'), 'text' => $t('Définissez ce que Cywise doit surveiller pour votre organisation.', 'Define what Cywise must monitor for your organization.')],
        ['title' => $t('Trouvez les risques importants.', 'Find the important risks.'), 'text' => $t('Cywise regroupe les signaux et met en évidence les constats qui nécessitent une action.', 'Cywise groups signals and highlights the findings that need action.')],
        ['title' => $t('Corrigez avec des conseils clairs.', 'Fix with clear guidance.'), 'text' => $t('Votre équipe reçoit des actions concrètes, sans complexité inutile.', 'Your team gets direct actions without unnecessary security complexity.')],
    ];

    // Hero accent = the matching solution's tone (see site/edge-card); rows = example data (acme.fr)
    $cases = [
        'find-vulnerabilities' => [
            'accent' => 'ui:text-medium',
            'title' => [$t('Trouvez ce qui peut', 'Find what can'), $t('céder.', 'break.')],
            'lede' => $t('Détectez les faiblesses et transformez-les en liste de correction priorisée.', 'Discover weaknesses and turn them into a prioritized remediation list.'),
            'panel' => $t('Vulnérabilités · acme.fr', 'Vulnerabilities · acme.fr'),
            'font' => $sans,
            'rows' => [
                ['critical', $t('Portail d’administration exposé', 'Exposed admin portal'), 'admin.acme.fr', $levels['critical']],
                ['high', $t('Composant web obsolète', 'Outdated web component'), 'www.acme.fr', $levels['high']],
                ['medium', $t('Configuration TLS', 'TLS configuration'), 'mail.acme.fr', $levels['medium']],
                ['low', $t('En-tête de sécurité manquant', 'Missing security header'), 'blog.acme.fr', $levels['low']],
            ],
            'features' => [
                [$t('Analyse de l’exposition', 'Scan exposure'), $t('Analysez les systèmes exposés sur Internet.', 'Review internet-facing systems.')],
                [$t('Identification des faiblesses', 'Identify weaknesses'), $t('Détectez les composants et configurations vulnérables connus.', 'Find known vulnerable components and configurations.')],
                [$t('Priorisation', 'Prioritize'), $t('Distinguez les urgences des constats à faible impact.', 'Separate urgent issues from low-impact findings.')],
                [$t('Explication du risque', 'Explain risk'), $t('Comprenez pourquoi chaque problème compte.', 'Understand why each issue matters.')],
                [$t('Attribution des actions', 'Assign action'), $t('Attribuez la bonne correction au bon responsable.', 'Give the right fix to the right owner.')],
                [$t('Suivi de la résolution', 'Track closure'), $t('Confirmez les progrès dans le temps.', 'Confirm progress over time.')],
            ],
            'steps' => $monitoring,
        ],
        'monitor-attack-surface' => [
            'accent' => 'ui:text-critical',
            'title' => [$t('Visualisez votre', 'See your'), $t('empreinte externe.', 'external footprint.')],
            'lede' => $t('Suivez les actifs publics et les services exposés lorsqu’ils évoluent.', 'Keep track of public assets and exposed services as they change.'),
            'panel' => $t('Changements · acme.fr', 'Changes · acme.fr'),
            'font' => $mono,
            'rows' => [
                ['critical', 'staging.acme.fr', '8080', $t('Nouveau', 'New')],
                ['medium', 'ftp.acme.fr', '21', $t('Nouveau', 'New')],
                ['low', 'www.acme.fr', '443', $t('Inchangé', 'Unchanged')],
                ['info', 'old.acme.fr', '443', $t('Retiré', 'Removed')],
            ],
            'features' => [
                [$t('Découverte des actifs', 'Discover assets'), $t('Identifiez les domaines, hôtes et services publics.', 'Find domains, hosts and public services.')],
                [$t('Détection des changements', 'Detect changes'), $t('Détectez l’apparition de nouvelles expositions.', 'See when new exposure appears.')],
                [$t('Réduction des inconnues', 'Reduce unknowns'), $t('Retrouvez les actifs oubliés ou non gérés.', 'Find forgotten or unmanaged assets.')],
                [$t('Priorisation de l’exposition', 'Prioritize exposure'), $t('Concentrez-vous sur les services publics à risque.', 'Focus on risky public services.')],
                [$t('Conservation de l’historique', 'Keep history'), $t('Suivez l’évolution de votre empreinte.', 'Track how your footprint evolves.')],
            ],
            'steps' => $monitoring,
        ],
        'check-leaked-credentials' => [
            'accent' => 'ui:text-low',
            'title' => [$t('Détectez les', 'Find'), $t('comptes compromis.', 'compromised accounts.')],
            'lede' => $t('Détectez les identifiants exposés et réduisez le risque de prise de contrôle de compte.', 'Detect exposed credentials and reduce account takeover risk.'),
            'panel' => $t('Identifiants · acme.fr', 'Credentials · acme.fr'),
            'font' => $mono,
            'rows' => [
                ['critical', 'j.martin@acme.fr', 'Admin', $t('Compromis', 'Compromised')],
                ['medium', 'compta@acme.fr', $t('Partagé', 'Shared'), $t('À changer', 'To change')],
                ['low', 's.durand@acme.fr', $t('Utilisateur', 'User'), $t('Réinitialisé', 'Reset')],
                ['low', 'l.bernard@acme.fr', $t('Utilisateur', 'User'), $t('Réinitialisé', 'Reset')],
            ],
            'features' => [
                [$t('Surveillance des domaines', 'Domain monitoring'), $t('Surveillez les identités liées aux domaines de l’entreprise.', 'Watch identities linked to company domains.')],
                [$t('Signaux de fuite', 'Leak signals'), $t('Détectez les identifiants présents dans des sources de fuite connues.', 'Find credentials present in known leak sources.')],
                [$t('Action utilisateur', 'User action'), $t('Indiquez aux utilisateurs ce qu’ils doivent modifier.', 'Tell users what to change.')],
                [$t('Revue des accès', 'Access review'), $t('Contrôlez les comptes sensibles après une fuite.', 'Review sensitive accounts after a leak.')],
                [$t('Contrôles continus', 'Ongoing checks'), $t('Poursuivez la détection après le nettoyage.', 'Continue detection after cleanup.')],
            ],
            'steps' => $monitoring,
        ],
        'create-pssi' => [
            'accent' => 'ui:text-info',
            'title' => [$t('Rédigez une politique que chacun peut', 'Write a policy people can'), $t('appliquer.', 'use.')],
            'lede' => $t('Créez une politique de sécurité structurée adaptée à votre organisation.', 'Create a structured security policy that fits your organization.'),
            'panel' => $t('PSSI · Acme', 'Security policy · Acme'),
            'font' => $sans,
            'rows' => [
                ['low', $t('Gestion des accès', 'Access management'), $t('DSI', 'IT'), $t('Validé', 'Approved')],
                ['low', $t('Sauvegardes', 'Backups'), 'Infra', $t('Validé', 'Approved')],
                ['medium', $t('Télétravail', 'Remote work'), $t('RH', 'HR'), $t('À revoir', 'To review')],
                ['info', $t('Gestion des incidents', 'Incident response'), $t('RSSI', 'CISO'), $t('Brouillon', 'Draft')],
            ],
            'features' => [
                [$t('Cadre de politique', 'Policy framework'), $t('Partez de domaines de sécurité concrets.', 'Start from practical security areas.')],
                [$t('Contexte', 'Context'), $t('Adaptez les contrôles à votre entreprise.', 'Adapt controls to your company.')],
                [$t('Responsabilités', 'Responsibilities'), $t('Définissez les responsables et les attentes.', 'Define owners and expectations.')],
                [$t('Langage clair', 'Readable language'), $t('Rendez les exigences claires pour les collaborateurs.', 'Make requirements clear to employees.')],
                [$t('Révision', 'Review'), $t('Maintenez la PSSI à jour.', 'Keep the PSSI current.')],
                [$t('Accompagnement', 'Support'), $t('Utilisez CyberBuddy pour clarifier les questions liées à la politique.', 'Use CyberBuddy to clarify policy questions.')],
            ],
            'steps' => null,
        ],
        'prepare-audit' => [
            'accent' => 'ui:text-brand-500',
            'title' => [$t('Transformez le travail de sécurité en', 'Turn security work into'), $t('preuves.', 'evidence.')],
            'lede' => $t('Organisez les constats, politiques et progrès avant un audit ou une revue client.', 'Organize findings, policies and progress before an audit or customer review.'),
            'panel' => $t('Audit · Acme', 'Audit · Acme'),
            'font' => $sans,
            'rows' => [
                ['critical', $t('Sauvegardes hors ligne', 'Offline backups'), $t('Contrôle', 'Control'), $t('Manquant', 'Missing')],
                ['medium', $t('Écarts ouverts', 'Open gaps'), '3', $t('En cours', 'In progress')],
                ['low', $t('Actions de remédiation', 'Remediation actions'), '12', $t('Suivi', 'Tracked')],
                ['low', $t('Politique de sécurité', 'Security policy'), 'PSSI', $t('Prêt', 'Ready')],
            ],
            'features' => [
                [$t('Visibilité des écarts', 'Gap visibility'), $t('Identifiez les contrôles manquants et les risques ouverts.', 'See missing controls and open risks.')],
                [$t('Préparation des politiques', 'Policy readiness'), $t('Passez en revue les documents de sécurité.', 'Review security documents.')],
                [$t('Preuves de remédiation', 'Remediation evidence'), $t('Présentez les actions et les progrès.', 'Show actions and progress.')],
                [$t('Vue de pilotage', 'Management view'), $t('Synthétisez la posture actuelle.', 'Summarize current posture.')],
                [$t('Suivi', 'Follow-up'), $t('Conservez une visibilité sur le travail après l’audit.', 'Keep work visible after the audit.')],
            ],
            'steps' => null,
        ],
        'run-pentest' => [
            'accent' => 'ui:text-brand-500',
            'title' => [$t('Testez avant que les attaquants', 'Test before'), $t('ne le fassent.', 'attackers do.')],
            'lede' => $t('Utilisez des tests experts pour éprouver les applications et infrastructures critiques.', 'Use expert testing to challenge critical applications and infrastructure.'),
            'panel' => $t('Rapport de pentest · app.acme.fr', 'Pentest report · app.acme.fr'),
            'font' => $sans,
            'rows' => [
                ['critical', $t('Injection SQL', 'SQL injection'), $t('Corrigé', 'Fixed'), $levels['critical']],
                ['high', $t('Contrôle d’accès défaillant', 'Broken access control'), $t('Corrigé', 'Fixed'), $levels['high']],
                ['medium', $t('Session sans expiration', 'Session never expires'), $t('Ouvert', 'Open'), $levels['medium']],
                ['low', $t('Divulgation de version', 'Version disclosure'), $t('Ouvert', 'Open'), $levels['low']],
            ],
            'features' => [
                [$t('Périmètre', 'Scope'), $t('Définissez les systèmes et les objectifs.', 'Define systems and goals.')],
                [$t('Reconnaissance', 'Reconnaissance'), $t('Examinez la surface d’attaque.', 'Review the attack surface.')],
                [$t('Tests manuels', 'Manual testing'), $t('Éprouvez les systèmes avec des techniques expertes.', 'Challenge systems with expert techniques.')],
                [$t('Preuves', 'Evidence'), $t('Documentez chaque faiblesse confirmée.', 'Document each confirmed weakness.')],
                [$t('Rapport priorisé', 'Prioritized report'), $t('Classez les constats par gravité.', 'Order findings by severity.')],
                [$t('Contre-test', 'Retest'), $t('Vérifiez les corrections importantes.', 'Verify important remediation.')],
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

    <x-site.cta :href="route('register')" :label="$t('Commencer →', 'Start protecting →')">
        <x-slot:title>{!! $t('Rendez votre<br>sécurité visible.', 'Make your<br>security visible.') !!}</x-slot:title>
    </x-site.cta>
</main>
