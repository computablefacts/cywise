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
            'accent' => 'ui:bg-medium ui:text-white ui:px-[0.06em] ui:box-decoration-clone',
            'title' => [$t('Trouvez vos failles avant', 'Find your flaws before'), $t('les attaquants.', 'attackers do.')],
            'lede' => $t('Détectez vos vulnérabilités et transformez-les en un plan d’action priorisé pour vos équipes.', 'Detect your vulnerabilities and turn them into a prioritized action plan for your teams.'),
            'story' => [
                'title' => $t("L'histoire de Thomas", "Thomas's story"),
                'text' => $t("Le patron de Thomas reçoit un email : un client exige un rapport de sécurité sous 15 jours. Thomas est seul. Il n'a pas d'outil de scan. Il renseigne son nom de domaine dans Cywise.\n\nTrente secondes plus tard, il a une liste : un framework obsolète (critique), un certificat TLS expiré (élevé), des en-têtes de sécurité absents (moyen). Il sait par où commencer. Il corrige, il suit, il exporte.\n\nQuinze jours plus tard, il envoie son rapport au client. Le contrat est maintenu. Et Thomas a un outil qu'il peut utiliser chaque mois.", "Thomas's boss receives an email: a client demands a security report within 15 days. Thomas is alone. He has no scanning tool. He enters his domain name into Cywise.\n\nThirty seconds later, he has a list: an obsolete framework (critical), an expired TLS certificate (high), missing security headers (medium). He knows where to start. He fixes, he tracks, he exports.\n\nFifteen days later, he sends his report to the client. The contract is maintained. And Thomas has a tool he can use every month."),
                'image' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&q=80&w=800',
            ],
            'for-whom' => [
                [$t('Responsable IT seul', 'IT Manager alone'), $t('« Mon patron me demande un rapport de sécurité. Je n\'ai pas d\'outil de scan. »', '"My boss asks me for a security report. I don\'t have a scanning tool."'), $t('Un scan en 30 secondes et une liste priorisée par criticité.', 'A scan in 30 seconds and a list prioritized by criticality.')],
                [$t('RSSI en PME', 'CISO in SMB'), $t('« J’ai 200 serveurs. Je ne sais pas lesquels sont vraiment fragiles. »', '"I have 200 servers. I don\'t know which ones are really fragile."'), $t('Une détection continue avec attribution des corrections par responsable.', 'Continuous detection with assignment of fixes by owner.')],
                [$t('CTO startup', 'Startup CTO'), $t('« Mon prospect exige un audit de sécurité avant de signer. »', '"My prospect demands a security audit before signing."'), $t('Un export propre, des preuves de correction, un suivi des progrès.', 'A clean export, proof of fixes, progress tracking.')],
                [$t('Équipe DevOps', 'DevOps Team'), $t('« On déploie vite. Les failles de config passent inaperçues. »', '"We deploy fast. Configuration flaws go unnoticed."'), $t('Un scan automatique à chaque nouveau déploiement.', 'An automatic scan at each new deployment.')],
            ],
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
            'accent' => 'ui:bg-critical ui:text-white ui:px-[0.06em] ui:box-decoration-clone',
            'title' => [$t('Maîtrisez votre', 'Master your'), $t('empreinte externe.', 'external footprint.')],
            'lede' => $t('Gardez le contrôle sur vos actifs publics et vos services exposés en temps réel.', 'Maintain control over your public assets and exposed services in real-time.'),
            'story' => [
                'title' => $t("L'histoire de Marie", "Marie's story"),
                'text' => $t("Lundi matin, Marie ouvre Cywise. Une alerte rouge : staging.acme.fr:8080 vient d'apparaître. C'est un environnement de test que son développeur a oublié de fermer dimanche soir.

Il expose des données clients, sans authentification, visible par n'importe qui. Sans Cywise, Marie n'aurait jamais su que ce service existait.

Avec Cywise, elle voit son périmètre en temps réel. Elle peut enfin répondre à la question que son DSI lui pose chaque mois : « On expose quoi, exactement ? »", "Monday morning, Marie opens Cywise. A red alert: staging.acme.fr:8080 has just appeared. It's a test environment that her developer forgot to close on Sunday evening.

It exposes customer data, without authentication, visible to anyone. Without Cywise, Marie would never have known this service existed.

With Cywise, she sees her perimeter in real-time. She can finally answer the question her CISO asks her every month: \"What exactly are we exposing?\""),
                'image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=800',
            ],
            'for-whom' => [
                [$t('DSI d\'une PME en croissance', 'CIO of a growing SMB'), $t('« Chaque semaine, un nouveau service apparaît. Je n\'ai pas de cartographie à jour. »', '"Every week, a new service appears. I don\'t have an up-to-date mapping."'), $t('Un inventaire automatique et continu de tous les actifs exposés.', 'An automatic and continuous inventory of all exposed assets.')],
                [$t('RSSI solo', 'Solo CISO'), $t('« Je suis seul. Je ne peux pas surveiller 200 sous-domaines manuellement. »', '"I\'m alone. I can\'t monitor 200 subdomains manually."'), $t('Des alertes automatiques dès qu\'un nouveau service apparaît ou disparaît.', 'Automatic alerts as soon as a new service appears or disappears.')],
                [$t('Responsable IT en ETI', 'IT Manager in a mid-sized company'), $t('« Après une fusion, j\'hérite d\'un SI que je ne connais pas. »', '"After a merger, I inherit an IT system I don\'t know."'), $t('Une découverte complète des actifs oubliés, obsolètes ou non gérés.', 'A complete discovery of forgotten, obsolete or unmanaged assets.')],
                [$t('Consultant / MSP', 'Consultant / MSP'), $t('« Mes clients me demandent un état des lieux de leur exposition. »', '"My clients ask me for an inventory of their exposure."'), $t('Un rapport clair, visuel, compréhensible par un non-technicien.', 'A clear, visual report, understandable by a non-technical person.')],
            ],
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
            'accent' => 'ui:bg-low ui:text-white ui:px-[0.06em] ui:box-decoration-clone',
            'title' => [$t('Protégez vos', 'Protect your'), $t('comptes compromis.', 'compromised accounts.')],
            'lede' => $t('Identifiez vos identifiants exposés et prévenez les risques de prise de contrôle de compte.', 'Identify your exposed credentials and prevent account takeover risks.'),
            'story' => [
                'title' => $t("L'histoire de Sophie", "Sophie's story"),
                'text' => $t("Le compte Instagram du plus gros client de Sophie publie des messages injurieux. Il a été piraté. Son community manager utilisait le même mot de passe partout — un mot de passe qui a fuité dans une base de données compromise six mois plus tôt.

Sophie renseigne @agence-sophie.com dans Cywise. Résultat : quatre collaborateurs compromis, dont le comptable.

Elle force la réinitialisation, active l'authentification à deux facteurs, programme une vérification mensuelle. La prochaine fois qu'une base de données fuit quelque part dans le monde, elle le saura avant les attaquants.", "Sophie's biggest client's Instagram account is publishing abusive messages. It has been hacked. Her community manager was using the same password everywhere — a password that leaked in a compromised database six months earlier.

Sophie enters @agence-sophie.com in Cywise. Result: four employees compromised, including the accountant.

She forces a reset, activates two-factor authentication, schedules a monthly check. The next time a database leaks somewhere in the world, she will know before the attackers."),
                'image' => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&q=80&w=800',
            ],
            'for-whom' => [
                [$t('Dirigeant de PME', 'SMB Leader'), $t('« Un compte piraté peut détruire la réputation de mon entreprise. »', '"A hacked account can destroy my company\'s reputation."'), $t('Une surveillance proactive des fuites de données pour son domaine.', 'Proactive monitoring of data leaks for their domain.')],
                [$t('RSSI', 'CISO'), $t('« Mes collaborateurs réutilisent leurs mots de passe, je le sais. »', '"My employees reuse their passwords, I know it."'), $t('La détection des comptes compromis avec leur niveau d\'accès.', 'Detection of compromised accounts with their access level.')],
                [$t('DRH / Direction', 'HR / Management'), $t('« Je suis responsable de la protection des données de mes équipes. »', '"I am responsible for protecting my teams\' data."'), $t('Un accompagnement des collaborateurs vers les actions de sécurisation.', 'Guidance for employees towards necessary security actions.')],
                [$t('MSP', 'MSP'), $t('« Mes clients ne comprennent pas le risque des mots de passe faibles. »', '"My clients don\'t understand the risk of weak passwords."'), $t('Un rapport simple qui montre qui est exposé et quoi faire.', 'A simple report showing who is exposed and what to do.')],
            ],
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
            'accent' => 'ui:bg-info ui:text-white ui:px-[0.06em] ui:box-decoration-clone',
            'title' => [$t('Transformez votre sécurité en', 'Turn your security into'), $t('preuves.', 'evidence.')],
            'lede' => $t('Centralisez vos preuves, vos politiques et vos actions avant vos audits et revues de conformité.', 'Centralize your evidence, policies and actions before your audits and compliance reviews.'),
            'story' => [
                'title' => $t('L\'histoire de Karim', 'Karim\'s story'),
                'text' => $t("Dans deux mois, un auditeur vient vérifier la conformité de Karim. Un contrat de deux millions d'euros en dépend. Karim a des documents éparpillés dans SharePoint, des tableurs Excel dans ses emails, des captures d'écran « au cas où ». Rien n'est centralisé.\n\nIl ouvre Cywise : trois contrôles manquants, douze actions en cours. Il assigne, il suit.\n\nLe jour de l'audit, au lieu de fouiller dans ses mails, il ouvre un tableau de bord unique. L'auditeur est satisfait, le contrat est signé. Et Karim ne referme pas le dossier : Cywise garde la trace de ses engagements pour le prochain audit.", "In two months, an auditor is coming to check Karim's compliance. A two-million-euro contract depends on it. Karim has documents scattered in SharePoint, Excel spreadsheets in his emails, screenshots \"just in case\". Nothing is centralized.\n\nHe opens Cywise: three missing controls, twelve actions in progress. He assigns, he tracks.\n\nOn the day of the audit, instead of rummaging through his emails, he opens a single dashboard. The auditor is satisfied, the contract is signed. And Karim doesn't close the file: Cywise keeps track of his commitments for the next audit."),
                'image' => 'https://images.unsplash.com/photo-1552581234-26160f608093?auto=format&fit=crop&q=80&w=800',
            ],
            'for-whom' => [
                [$t('DSI avant une certification', 'CIO before certification'), $t('« L\'auditeur arrive dans deux mois. Mes preuves sont éparpillées. »', '"The auditor arrives in two months. My evidence is scattered."'), $t('Une centralisation des écarts, des actions et des documents de conformité.', 'Centralization of gaps, actions and compliance documents.')],
                [$t('Responsable conformité', 'Compliance Manager'), $t('« Je passe mon temps à collecter des preuves dans les emails. »', '"I spend my time collecting evidence in emails."'), $t('Un tableau de bord unique avec l\'évolution de la posture dans le temps.', 'A single dashboard with the evolution of the posture over time.')],
                [$t('RSSI en secteur régulé', 'CISO in regulated sector'), $t('« Chaque année, le régulateur demande plus de justificatifs. »', '"Each year, the regulator asks for more justifications."'), $t('Une traçabilité continue, pas juste une course avant l\'audit.', 'Continuous traceability, not just a rush before the audit.')],
                [$t('MSP / Cabinet de conseil', 'MSP / Consulting firm'), $t('« Mes clients veulent un accompagnement jusqu’à la certification. »', '"My clients want support until certification."'), $t('Un pilotage stratégique que le client peut montrer à son COMEX.', 'Strategic steering that the client can show to their executive committee.')],
            ],
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
            'accent' => 'ui:bg-ink ui:text-white ui:px-[0.06em] ui:box-decoration-clone',
            'title' => [$t('Testez vos défenses avant que', 'Test your defenses before'), $t('l’attaque n’ait lieu.', 'the attack occurs.')],
            'lede' => $t('Faites évaluer vos applications et infrastructures critiques par nos experts en sécurité offensive.', 'Have your critical applications and infrastructures evaluated by our offensive security experts.'),
            'story' => [
                'title' => $t("L'histoire d'Amandine", "Amandine's story"),
                'text' => $t("Amandine vient de lever cinq millions d'euros. Son plus gros prospect, une banque, exige un rapport de pentest indépendant avant de signer. Elle a trois semaines.

Son équipe fait des revues de code, mais une banque veut un test humain avec des preuves. Elle cadre le périmètre dans Cywise : l'API, le portail client, l'interface d'administration.

Cinq jours plus tard, le rapport tombe. Deux vulnérabilités critiques — corrigées immédiatement. Elle transmet le rapport. Le contrat est signé. La banque exige même un pentest annuel. Amandine sait déjà à qui elle fera appel.", "Amandine has just raised five million euros. Her biggest prospect, a bank, demands an independent pentest report before signing. She has three weeks.

Her team does code reviews, but a bank wants a human test with evidence. She scopes the perimeter in Cywise: the API, the customer portal, the administration interface.

Five days later, the report comes out. Two critical vulnerabilities — fixed immediately. She sends the report. The contract is signed. The bank even demands an annual pentest. Amandine already knows who she will call."),
                'image' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&q=80&w=800',
            ],
            'for-whom' => [
                [$t('CTO startup / scale-up', 'Startup / Scale-up CTO'), $t('« Mon prospect banque/assurance exige un pentest indépendant. »', '"My bank/insurance prospect demands an independent pentest."'), $t('Un rapport reconnu, avec preuves, méthodologie et recommandations.', 'A recognized report, with evidence, methodology and recommendations.')],
                [$t('DSI grande entreprise', 'Large enterprise CIO'), $t('« Je fais des scans automatiques, mais j\'ai besoin d\'un regard humain. »', '"I do automatic scans, but I need a human eye."'), $t('Des simulations d’attaque par des experts offensifs sur les systèmes critiques.', 'Attack simulations by offensive experts on critical systems.')],
                [$t('Responsable sécurité applicative', 'Application Security Manager'), $t('« Mon équipe développe vite. Je veux valider que nos correctifs tiennent. »', '"My team develops fast. I want to validate that our fixes hold."'), $t('Un test ciblé sur l’application avec validation post-correction.', 'A targeted test on the application with post-correction validation.')],
                [$t('Porteur de projet sensible', 'Sensitive project owner'), $t('« Je lance un nouveau service. Je veux être sûr avant la mise en prod. »', '"I am launching a new service. I want to be sure before going live."'), $t('Un cadrage précis du périmètre et des objectifs de sécurité.', 'Precise scoping of the perimeter and security objectives.')],
            ],
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
            <x-site.display as="h1" size="xl">{{ $useCase['title'][0] }} <span class="{{ $useCase['accent'] }}">{{ $useCase['title'][1] }}</span></x-site.display>
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
 
    {{-- Story --}}
    @if(isset($useCase['story']))
        <section class="ui:border-0 ui:border-t ui:border-solid ui:border-line ui:bg-white">
            <div class="{{ $wrap }} ui:py-16">
                <div class="ui:grid ui:grid-cols-1 ui:gap-12 ui:lg:grid-cols-2 ui:items-center">
                    <div>
                        <span class="ui:font-mono ui:text-sm ui:uppercase ui:tracking-widest ui:text-brand-500">{{ $t('L\'histoire', 'The story') }}</span>
                        <x-site.display class="ui:mt-4">{{ $useCase['story']['title'] }}</x-site.display>
                        <div class="ui:mt-6 ui:text-lg ui:leading-relaxed ui:text-slate-600 ui:space-y-4">
                            {!! nl2br(e($useCase['story']['text'])) !!}
                        </div>
                    </div>
                    <div class="ui:relative">
                        <div class="ui:aspect-video ui:rounded-2xl ui:bg-slate-100 ui:overflow-hidden ui:flex ui:items-center ui:justify-center ui:shadow-lg">
                            @if(isset($useCase['story']['image']))
                                <img src="{{ $useCase['story']['image'] }}" alt="{{ $useCase['story']['title'] }}" class="ui:w-full ui:h-full ui:object-cover">
                            @else
                                <div class="ui:text-6xl">✍️</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
 
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
 
    {{-- For Whom --}}
    @if(isset($useCase['for-whom']))
        <section class="ui:bg-white">
            <div class="{{ $wrap }} ui:py-24">
                <span class="ui:font-mono ui:text-sm ui:uppercase ui:tracking-widest ui:text-brand-500">{{ $t('Pour qui ?', 'For whom?') }}</span>
                <x-site.display class="ui:mt-4">{{ $t('Vous vous reconnaissez ?', 'Do you recognize yourself?') }}</x-site.display>
                <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-4 ui:sm:grid-cols-2 ui:lg:grid-cols-4">
                    @foreach($useCase['for-whom'] as [$profile, $problem, $benefit])
                        <div class="ui:flex ui:flex-col ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-slate-50 ui:p-6 ui:transition-colors ui:hover:bg-slate-100">
                            <h4 class="ui:m-0 ui:text-lg ui:font-bold ui:leading-tight ui:text-slate-900">{{ $profile }}</h4>
                            <p class="ui:m-0 ui:mt-4 ui:flex-1 ui:text-sm ui:italic ui:leading-relaxed ui:text-slate-500">
                                {{ $problem }}
                            </p>
                            <div class="ui:mt-6 ui:pt-6 ui:border-0 ui:border-t ui:border-solid ui:border-line">
                                <p class="ui:m-0 ui:text-[15px] ui:leading-normal ui:text-slate-700">
                                    <span class="ui:block ui:font-mono ui:text-[10px] ui:uppercase ui:tracking-wider ui:text-brand-500 ui:mb-2">{{ $t('Notre apport', 'Our value') }}</span>
                                    {{ $benefit }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
 
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
