@php
    // Company page, shared by pages/la-societe (fr) and pages/en/company (en).
    $isEnglish = $locale === 'en';
    $t = fn (string $fr, string $en) => $isEnglish ? $en : $fr;
    $wrap = 'ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:sm:px-6';
    $cardTitle = 'ui:m-0 ui:text-2xl ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%';

    $pillars = [
        [$t('Visibilité complète', 'Full visibility'), $t('Sur votre état de sécurité, en continu.', 'On your security posture, continuously.')],
        [$t('Automatisation', 'Automation'), $t('Des tâches techniques, pour compenser le manque de ressources internes.', 'Of technical tasks, to make up for limited in-house resources.')],
        [$t('Chatbot pédagogique', 'Teaching chatbot'), $t('Pour répondre à vos questions et vous former en même temps.', 'To answer your questions and train you at the same time.')],
        [$t('Accompagnement humain', 'Human support'), $t('Quand c’est nécessaire : ticket, support, hotline.', 'When needed: ticket, support, hotline.')],
        [$t('Tarification TPE/PME', 'SMB pricing'), $t('Claire, transparente, sans surprise.', 'Clear, transparent, no surprises.')],
        [$t('Modulaire et évolutive', 'Modular and scalable'), $t('Vous commencez petit, vous adaptez au fur et à mesure.', 'Start small, adapt as you go.')],
    ];

    $values = [
        [$t('Honnêteté & Intégrité', 'Honesty & Integrity'), $t('« On dit ce qu’on fait. On fait ce qu’on dit. » Et on ne vend pas des promesses vides. Ni à nos clients, ni entre nous.', '“We say what we do. We do what we say.” And we don’t sell empty promises. Not to our customers, not to each other.')],
        [$t('Partage & Transmission', 'Sharing & Teaching'), $t('La cybersécurité, on ne la garde pas pour nous. On la simplifie, on la vulgarise, on la transmet. Avec pédagogie. Et quand on peut, avec humour.', 'We don’t keep cybersecurity to ourselves. We simplify it, explain it, pass it on. Patiently. And with humour when we can.')],
        [$t('Curiosité & Persévérance', 'Curiosity & Perseverance'), $t('Notre IA est curieuse par nature. Et nous aussi. On gratte, on teste, on creuse, on ajuste. Jusqu’à ce que ça fonctionne !', 'Our AI is curious by nature. So are we. We scratch, test, dig and adjust. Until it works!')],
        [$t('Fiabilité', 'Reliability'), $t('On déteste les bugs (et les tickets !). Alors on développe proprement, on vérifie, et on livre un produit sérieux.', 'We hate bugs (and tickets!). So we build cleanly, we check, and we ship a serious product.')],
        [$t('Accessibilité & Inclusion', 'Accessibility & Inclusion'), $t('Pas besoin d’un doctorat en cybersécurité pour utiliser Cywise. Notre solution est pensée pour les TPE/PME. Elle est accessible techniquement et financièrement !', 'No PhD in cybersecurity needed to use Cywise. It is built for SMBs, and affordable both technically and financially!')],
    ];

    // [photo, name, role, bio]
    $team = [
        ['team-csavelief.jpg', 'Cyrille Savelief', $t('Président', 'CEO'), $t('Président et cofondateur de Cywise. Plus de 15 ans d’expérience dans la collecte et le traitement de données.', 'CEO and co-founder of Cywise. Over 15 years of experience in data collection and processing.')],
        ['team-pduteil.jpg', 'Pierre Duteil', $t('Directeur technique', 'CTO'), $t('Directeur technique et cofondateur de Cywise. Plus de 15 ans d’expérience en cybersécurité offensive et défensive.', 'CTO and co-founder of Cywise. Over 15 years of experience in offensive and defensive cybersecurity.')],
        ['team-eesmerian.jpg', 'Eric Esmérian', $t('Directeur commercial', 'Sales Director'), $t('Dans le développement de startups depuis 2003. Il organise avec Cyrille et Pierre la version industrielle de l’entreprise.', 'Growing startups since 2003. He builds the industrial version of the company with Cyrille and Pierre.')],
        ['team-pbrisacier.jpg', 'Patrick Brisacier', 'R&D', $t('Responsable R&D. Plus de 20 ans d’expérience dans la conception, le déploiement et l’optimisation d’infrastructures complexes.', 'Head of R&D. Over 20 years of experience designing, deploying and optimising complex infrastructures.')],
        ['team-jjkhalife.jpg', 'Jean-Jamil Khalifé', $t('Expert cybersécurité', 'Cybersecurity expert'), $t('Pentester et chercheur en sécurité informatique, il améliore nos outils et règles de détection.', 'Pentester and security researcher, he improves our tools and detection rules.')],
        ['team-bguillot.jpg', 'Bérangère Guillot', 'Customer Success', $t('Experte de la relation client, elle aide nos clients à tirer le meilleur de nos produits et services.', 'Customer relations expert, she helps our customers get the most out of our products and services.')],
    ];

    $actions = [
        $t('Lancer un audit express', 'Run an express audit'),
        $t('Générer votre charte informatique', 'Generate your IT charter'),
        $t('Déployer un honeypot en deux temps trois mouvements', 'Deploy a honeypot in no time'),
        $t('Être conforme à NIS2 sans complexité', 'Comply with NIS2 without the hassle'),
    ];
@endphp

<main>
    {{-- Hero --}}
    <section class="{{ $wrap }} ui:pb-20 ui:pt-14">
        <x-site.display as="h1" size="xl" class="ui:text-[clamp(24px,8vw,100px)]!">{!! $t('La cybersécurité<br><span class="ui:text-brand-500">pour les mortels.</span>', 'Cybersecurity<br><span class="ui:text-brand-500">for mere mortals.</span>') !!}</x-site.display>
        <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-6 ui:text-[21px] ui:leading-normal ui:text-slate-700 ui:lg:grid-cols-2 ui:lg:gap-12">
            <p class="ui:m-0">{{ $t('Cywise est une plateforme SaaS française qui accompagne les entreprises de toutes tailles dans leur cyberprotection, leur cyberdéfense et leur cyberrésilience.', 'Cywise is a French SaaS platform helping companies of all sizes with their cyber protection, cyber defence and cyber resilience.') }}</p>
            <p class="ui:m-0">{{ $t('Notre ambition : rendre la cybersécurité simple, accessible et opérationnelle, même sans expertise technique.', 'Our ambition: make cybersecurity simple, accessible and actionable, even without technical expertise.') }}</p>
        </div>
    </section>

    {{-- Vision: statement, then what it means for customers --}}
    <section class="ui:border-0 ui:border-y ui:border-solid ui:border-line ui:bg-canvas">
        <div class="{{ $wrap }} ui:py-22">
            <div class="ui:grid ui:grid-cols-1 ui:gap-8 ui:lg:grid-cols-2 ui:lg:gap-12">
                <x-site.display>{{ $t('La vision', 'Our vision') }}</x-site.display>
                <div class="ui:text-lg ui:leading-relaxed ui:text-slate-700">
                    <p class="ui:m-0 ui:text-[22px] ui:font-semibold ui:leading-snug ui:text-ink">{{ $t('Rendre la cybersécurité aussi simple et naturelle que de planifier une réunion.', 'Make cybersecurity as simple and natural as scheduling a meeting.') }}</p>
                    <p class="ui:m-0 ui:mt-5">{{ $t('Qu’un jour, « faire un Cywise » devienne un réflexe pour toutes les entreprises. Fini la complexité, place à une protection accessible, intuitive et efficace.', 'One day, “doing a Cywise” should be a reflex for every company. No more complexity: protection that is accessible, intuitive and effective.') }}</p>
                    <p class="ui:m-0 ui:mt-5">{{ $t('Nous croyons en une cybersécurité qui n’exclut personne, pas même les plus petites structures. Parce qu’être en sécurité ne devrait jamais être une question de taille.', 'We believe in cybersecurity that leaves no one out, not even the smallest organisations. Because being safe should never depend on size.') }}</p>
                </div>
            </div>

            <p class="ui:m-0 ui:mt-16 ui:font-mono ui:text-[13px] ui:uppercase ui:tracking-wider ui:text-slate-500">{{ $t('Pas besoin d’être un expert pour protéger votre entreprise', 'No need to be an expert to protect your company') }}</p>
            <div class="ui:mt-6 ui:grid ui:grid-cols-1 ui:gap-3 ui:sm:grid-cols-2 ui:lg:grid-cols-3">
                @foreach($pillars as [$title, $text])
                    <article class="ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-6">
                        <h3 class="{{ $cardTitle }}">{{ $title }}</h3>
                        <p class="ui:m-0 ui:mt-3 ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Story --}}
    <section class="{{ $wrap }} ui:grid ui:grid-cols-1 ui:gap-8 ui:py-24 ui:lg:grid-cols-2 ui:lg:gap-12">
        <x-site.display>{!! $t('La petite<br>histoire', 'Our<br>story') !!}</x-site.display>
        <div class="ui:text-lg ui:leading-relaxed ui:text-slate-700">
            <p class="ui:m-0">{{ $t('Cyrille et Pierre se sont rencontrés il y a 15 ans, alors qu’ils étaient encore étudiants, unis par leur fascination pour la magie, où la maîtrise de l’illusion et de la dissimulation joue un rôle central. Des principes que l’on retrouve aujourd’hui dans les honeypots développés par Cywise, conçus pour tromper et piéger les cyberattaquants.', 'Cyrille and Pierre met 15 years ago as students, brought together by a fascination for magic, where mastering illusion and concealment is everything. The same principles drive the honeypots Cywise builds today, designed to deceive and trap attackers.') }}</p>
            <p class="ui:m-0 ui:mt-5">{{ $t('Après des parcours distincts — Cyrille, diplômé du Master Parisien de Recherche en Informatique, a fait ses armes chez FactSet dans la gestion de données, tandis que Pierre, diplômé de l’EPITA, a œuvré au ministère de l’Intérieur comme spécialiste en cyber offensive — ils ont chacun fondé et dirigé leurs propres entreprises.', 'They then took separate paths — Cyrille, a graduate of the Parisian Master of Research in Computer Science, cut his teeth in data management at FactSet, while Pierre, an EPITA graduate, worked at the French Ministry of the Interior as an offensive cyber specialist — and each founded and ran his own company.') }}</p>
            <p class="ui:m-0 ui:mt-5">{{ $t('En 2018, ils unissent leurs forces pour créer ComputableFacts, aujourd’hui Cywise, avec une ambition claire : rendre la cybersécurité accessible à tous, y compris aux TPE et PME.', 'In 2018, they joined forces to create ComputableFacts, now Cywise, with a clear goal: make cybersecurity accessible to everyone, small businesses included.') }}</p>
            <p class="ui:m-0 ui:mt-5">{{ $t('Cywise est le fruit de quatre années de recherche et développement, où se mêlent expertise en données et cybersécurité.', 'Cywise is the result of four years of research and development, blending data and cybersecurity expertise.') }}</p>
        </div>
    </section>

    {{-- Values: ink band --}}
    <section class="ui:bg-ink ui:text-white">
        <div class="{{ $wrap }} ui:py-22">
            <x-site.display>{{ $t('Nos valeurs', 'Our values') }}</x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[620px] ui:text-lg ui:leading-relaxed ui:text-slate-400">{{ $t('On ne fait pas que du code et des audits. On avance avec des convictions.', 'We don’t just write code and run audits. We move forward with convictions.') }}</p>
            <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-2 ui:lg:grid-cols-3">
                @foreach($values as [$title, $text])
                    <article class="ui:rounded-2xl ui:border ui:border-solid ui:border-slate-800 ui:p-6">
                        <h3 class="{{ $cardTitle }} ui:text-brand-500">{{ $title }}</h3>
                        <p class="ui:m-0 ui:mt-3 ui:text-base ui:leading-normal ui:text-slate-300">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Team --}}
    <section class="{{ $wrap }} ui:py-24">
        <x-site.display>{{ $t('L’équipe', 'The team') }}</x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[620px] ui:text-lg ui:leading-relaxed ui:text-slate-700">{{ $t('Un groupe d’individus passionnés par ce qu’ils font et déterminés à offrir les meilleures solutions à nos utilisateurs.', 'A group of people passionate about what they do and set on giving our users the best solutions.') }}</p>
        <ul class="ui:m-0 ui:mt-12 ui:grid ui:list-none ui:grid-cols-1 ui:gap-x-6 ui:gap-y-12 ui:p-0 ui:sm:grid-cols-2 ui:lg:grid-cols-3">
            @foreach($team as [$photo, $name, $role, $bio])
                <li>
                    <img class="ui:aspect-[4/5] ui:w-full ui:rounded-2xl ui:object-cover" src="{{ asset('cywise/img/' . $photo) }}" alt="{{ $name }}" loading="lazy">
                    <h3 class="ui:m-0 ui:mt-5 ui:text-2xl ui:font-extrabold ui:tracking-tight">{{ $name }}</h3>
                    <p class="ui:m-0 ui:mt-1 ui:font-mono ui:text-[13px] ui:uppercase ui:tracking-wider ui:text-brand-700">{{ $role }}</p>
                    <p class="ui:m-0 ui:mt-3 ui:text-base ui:leading-normal ui:text-slate-600">{{ $bio }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- What you can do right away, then the closing call to action --}}
    <section class="{{ $wrap }} ui:grid ui:grid-cols-1 ui:gap-8 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:py-16 ui:lg:grid-cols-2 ui:lg:gap-12">
        <p class="ui:m-0 ui:text-xl ui:leading-normal ui:text-slate-700">{{ $t('Cywise s’installe en quelques clics, détecte vos risques automatiquement, et vous accompagne dans toutes vos démarches. Avec toujours CyberBuddy à vos côtés !', 'Cywise sets up in a few clicks, detects your risks automatically and guides you every step of the way. With CyberBuddy always by your side!') }}</p>
        <ul class="ui:m-0 ui:list-none ui:p-0">
            @foreach($actions as $action)
                <li class="ui:flex ui:items-center ui:gap-3 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:py-3.5 ui:text-lg ui:font-semibold">
                    <x-phosphor-check-bold class="ui:size-5 ui:shrink-0 ui:text-brand-500"/>
                    {{ $action }}
                </li>
            @endforeach
        </ul>
    </section>

    <x-site.cta :href="route('tools.cybercheck.init')" :label="$t('Démarrer mon audit gratuit →', 'Start my free audit →')">
        <x-slot:title>{!! $t('Prêt à sécuriser<br>votre activité ?', 'Ready to secure<br>your business?') !!}</x-slot:title>
    </x-site.cta>
</main>
