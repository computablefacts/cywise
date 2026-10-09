@php
    // Home page, shared by pages/index (fr) and pages/en/index (en).
    $isEnglish = $locale === 'en';
    $t = fn (string $fr, string $en) => $isEnglish ? $en : $fr;
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
    $wrap = 'ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:sm:px-6';

    $stats = [
        ['24/7', $t('Surveillance', 'Monitoring'), 'ui:bg-ink ui:text-white', 'ui:text-slate-400'],
        ['1K+', $t('Serveurs', 'Servers'), 'ui:bg-brand-500 ui:text-white', 'ui:text-brand-50'],
        ['170B+', $t('Identifiants', 'Credentials'), 'ui:bg-slate-100', 'ui:text-slate-600'],
    ];

    $solutions = [
        ['critical', $t('Surface d’attaque', 'Attack surface'), $t('Voyez ce que les attaquants voient.', 'See what attackers see.'), $t('Surveillez les domaines publics, serveurs, ports et services exposés.', 'Monitor public domains, servers, ports and exposed services.'), 'solutions.attack-surface'],
        ['medium', $t('Vulnérabilités', 'Vulnerabilities'), $t('Trouvez les points faibles.', 'Find the weak points.'), $t('Identifiez les vulnérabilités et concentrez-vous sur les risques les plus importants.', 'Identify vulnerabilities and focus on the most important risks.'), 'solutions.vulnerability-management'],
        ['low', $t('Identifiants', 'Credentials'), $t('Sachez ce qui a fuité.', 'Know what leaked.'), $t('Détectez les identifiants compromis liés à votre entreprise.', 'Detect compromised credentials linked to your company.'), 'solutions.credential-monitoring'],
        ['brand', 'CyberBuddy', $t('Demandez. Comprenez. Agissez.', 'Ask. Understand. Act.'), $t('Obtenez des conseils clairs adaptés au contexte de votre entreprise.', 'Get clear cybersecurity guidance from your company context.'), 'solutions.cyberbuddy'],
        ['info', 'PSSI', $t('Créez votre politique de sécurité.', 'Build your security policy.'), $t('Créez une politique de sécurité pratique pour votre organisation.', 'Create a practical security policy for your organization.'), 'solutions.pssi'],
        ['ink', 'Pentest', $t('Faites tester par des experts.', 'Put humans on the attack.'), $t('Faites tester vos applications critiques par des experts en sécurité.', 'Test critical applications with experienced security experts.'), 'solutions.pentest'],
    ];

    $audiences = [
        [$t('PME', 'SMBs'), $t('Protégez l’entreprise sans opérations de sécurité lourdes.', 'Protect the business without heavy security operations.'), 'audiences.smbs'],
        [$t('Équipes IT', 'IT teams'), $t('Voyez clairement les risques et sachez quoi corriger en premier.', 'See risks clearly and know what to fix first.'), 'audiences.it-teams'],
        [$t('RSSI', 'CISOs'), $t('Centralisez la visibilité et facilitez les décisions de sécurité.', 'Centralize visibility and support security decisions.'), 'audiences.cisos'],
        ['MSP', $t('Gérez la visibilité sécurité sur plusieurs environnements clients.', 'Manage security visibility across customer environments.'), 'audiences.msps'],
    ];

    $pentest = [
        [$t('Mission', 'Engagement'), $t('4 jours', '4 days')],
        [$t('Livrable', 'Deliverable'), $t('1 rapport', '1 report')],
        [$t('À partir de', 'Starting at'), $t('3 000 €', '€3,000')],
    ];
@endphp

<main>
    {{-- Hero --}}
    <section class="{{ $wrap }} ui:pb-20 ui:pt-14">
        <x-site.display as="h1" size="xl">{!! $t('Votre<br>entreprise<br>est déjà<br><span class="ui:text-brand-500">une cible.</span>', 'Your<br>company<br>is already<br><span class="ui:text-brand-500">a target.</span>') !!}</x-site.display>

        {{-- Lead and actions on one row, key figures full width below --}}
        <div class="ui:mt-12 ui:flex ui:flex-wrap ui:items-end ui:justify-between ui:gap-8">
            <p class="ui:m-0 ui:max-w-[620px] ui:text-[21px] ui:leading-normal ui:text-slate-700">
                {{ $t('Cywise détecte vos actifs exposés, vos vulnérabilités et vos identifiants compromis avant les attaquants.', 'Cywise finds your exposed assets, vulnerabilities and leaked credentials before attackers do.') }}
            </p>
            <div class="ui:flex ui:flex-wrap ui:gap-3">
                <x-site.button :href="route('register')">{{ $t('Commencer →', 'Start protecting →') }}</x-site.button>
                <x-site.button variant="outline" href="#solutions">{{ $t('Découvrir Cywise', 'Explore Cywise') }}</x-site.button>
            </div>
        </div>
        <div class="ui:mt-10 ui:grid ui:grid-cols-3 ui:gap-2.5 ui:sm:gap-3">
            @foreach($stats as [$value, $label, $tile, $muted])
                <div class="ui:min-w-0 ui:rounded-2xl ui:px-3 ui:py-4 ui:sm:p-7 {{ $tile }}">
                    <div class="ui:text-[clamp(20px,5vw,64px)] ui:font-black ui:leading-none ui:tracking-tight ui:font-stretch-125%">{{ $value }}</div>
                    <div class="ui:mt-2 ui:font-mono ui:text-[11px] ui:uppercase ui:tracking-wider ui:sm:text-[13px] {{ $muted }}">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Problem: visibility → priority → action --}}
    <section class="ui:border-0 ui:border-y ui:border-solid ui:border-line ui:bg-canvas">
        <div class="{{ $wrap }} ui:py-22">
            <x-site.display class="ui:max-w-[980px]">{{ $t('La cybersécurité ne devrait pas exiger une équipe de 50 personnes.', 'Security should not need a 50-person team.') }}</x-site.display>
            <div class="ui:mt-12 ui:grid ui:gap-3 ui:md:grid-cols-3">
                @foreach([$t('Sachez ce qui est exposé.', 'Know what is exposed.'), $t('Sachez ce qui compte.', 'Know what matters.'), $t('Sachez quoi corriger.', 'Know what to fix.')] as $line)
                    <div class="ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-6 ui:text-[26px] ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%">{{ $line }}</div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Solutions --}}
    <section id="solutions" class="{{ $wrap }} ui:py-24">
        <div class="ui:flex ui:flex-wrap ui:items-end ui:justify-between ui:gap-6">
            <x-site.display>{!! $t('Une plateforme.<br><span class="ui:text-slate-400">Plusieurs défenses.</span>', 'One platform.<br><span class="ui:text-slate-400">Multiple defenses.</span>') !!}</x-site.display>
            <p class="ui:m-0 ui:max-w-[380px] ui:text-lg ui:leading-normal ui:text-slate-600">{{ $t('Des outils simples pour les tâches de sécurité que votre entreprise doit gérer chaque jour.', 'Simple tools for the security work your company must do every day.') }}</p>
        </div>
        <div class="ui:mt-12 ui:grid ui:gap-3 ui:md:grid-cols-2 ui:xl:grid-cols-3">
            @foreach($solutions as [$tone, $name, $title, $text, $route])
                <x-site.edge-card :tone="$tone" :href="route($routePrefix . $route)">
                    <span class="ui:font-mono ui:text-[13px] ui:uppercase ui:text-slate-500">{{ $name }}</span>
                    <span class="ui:text-2xl ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%">{{ $title }}</span>
                    <span class="ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</span>
                    <span class="ui:mt-2 ui:text-sm ui:font-bold ui:uppercase ui:group-hover:text-brand-700">{{ $t('Découvrir →', 'Explore →') }}</span>
                </x-site.edge-card>
            @endforeach
        </div>
    </section>

    {{-- CyberBuddy: title above, then the app's own chat window (Inter, same tokens as pages/cyberbuddy) --}}
    <section class="ui:bg-brand-500 ui:text-white">
        <div class="{{ $wrap }} ui:py-24">
            <x-site.display class="ui:max-w-[1000px] ui:text-[clamp(24px,5vw,76px)]!">{{ $t('Votre équipe cybersécurité a un collègue IA.', 'Your cybersecurity team got an AI colleague.') }}</x-site.display>
            <div class="ui:mt-12 ui:grid ui:grid-cols-1 ui:items-start ui:gap-10 ui:lg:grid-cols-3">
                <div class="ui:max-w-[360px]">
                    <p class="ui:m-0 ui:text-[19px] ui:leading-normal ui:text-brand-50">{{ $t('Posez des questions directes sur les risques, les politiques et les actions de sécurité.', 'Ask direct questions about risks, policies and security actions.') }}</p>
                    <x-site.button variant="dark" class="ui:mt-7" :href="route($routePrefix . 'solutions.cyberbuddy')">{{ $t('Découvrir CyberBuddy →', 'Meet CyberBuddy →') }}</x-site.button>
                </div>
                <div class="ui:min-w-0 ui:overflow-hidden ui:rounded-xl ui:border ui:border-solid ui:border-brand-200 ui:bg-white ui:font-sans ui:text-ink ui:shadow-2xl ui:lg:col-span-2" aria-hidden="true">
                    <div class="ui:flex ui:items-center ui:gap-3 ui:border-0 ui:border-b ui:border-solid ui:border-line ui:px-5 ui:py-4">
                        <span class="ui:flex ui:size-10 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-xl ui:bg-brand-500 ui:text-white"><x-phosphor-robot-bold class="ui:size-5"/></span>
                        <span class="ui:flex ui:flex-col">
                            <span class="ui:text-lg ui:font-semibold ui:leading-6">CyberBuddy</span>
                            <span class="ui:text-sm ui:text-slate-500">{{ $t('Votre assistant IA en cybersécurité', 'Your AI cybersecurity assistant') }}</span>
                        </span>
                    </div>
                    <div class="ui:flex ui:flex-col ui:gap-5 ui:p-5">
                        <div class="ui:max-w-[75%] ui:self-end ui:rounded-2xl ui:rounded-br-sm ui:bg-ink ui:px-4 ui:py-3 ui:text-sm ui:text-white">{{ $t('Certains de nos serveurs publics sont-ils vulnérables ?', 'Are any of our public servers vulnerable?') }}</div>
                        <div class="ui:flex ui:items-start ui:gap-2.5">
                            <span class="ui:flex ui:rounded-full ui:bg-brand-50 ui:p-2 ui:text-brand-500"><x-phosphor-robot-bold class="ui:size-4"/></span>
                            <div class="ui:min-w-0 ui:max-w-[85%] ui:rounded-2xl ui:rounded-bl-sm ui:border ui:border-solid ui:border-line ui:px-4 ui:py-3 ui:text-sm ui:leading-relaxed">
                                {{ $t('Oui. Trois éléments demandent une action.', 'Yes. Three findings require action.') }}
                                <ul class="ui:m-0 ui:mt-3 ui:list-none ui:overflow-hidden ui:rounded-lg ui:border ui:border-solid ui:border-line ui:p-0">
                                    @foreach([['critical', $t('Critique', 'Critical'), $t('Portail d’administration exposé', 'Exposed administration portal')], ['high', $t('Élevé', 'High'), $t('Composant web obsolète', 'Outdated web component')], ['medium', $t('Moyen', 'Medium'), $t('Configuration TLS', 'TLS configuration')]] as [$level, $label, $title])
                                        <li data-severity="{{ $level }}" class="ui:flex ui:items-center ui:gap-2.5 ui:px-3 ui:py-2.5 {{ $loop->first ? '' : 'ui:border-0 ui:border-t ui:border-solid ui:border-line' }}">
                                            <x-ui.badge :level="$level">{{ $label }}</x-ui.badge>
                                            {{ $title }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-4">
                        <div class="ui:flex ui:items-center ui:gap-2">
                            <span class="ui:block ui:h-10 ui:min-w-0 ui:flex-1 ui:truncate ui:leading-10 ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-slate-50 ui:px-4 ui:text-sm ui:text-slate-400">{{ $t('Posez-moi une question !', 'Ask me anything!') }}</span>
                            <x-phosphor-image class="ui:size-5 ui:text-slate-500"/>
                            <span class="ui:flex ui:h-10 ui:items-center ui:gap-2 ui:rounded-lg ui:bg-brand-500 ui:px-4 ui:text-sm ui:font-medium ui:text-white">{{ $t('Envoyer', 'Send') }} <x-phosphor-paper-plane-right class="ui:size-4"/></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- For whom --}}
    <section class="{{ $wrap }} ui:py-24">
        <x-site.display class="ui:max-w-[900px]">{{ $t('Conçu pour les équipes qui veulent de la clarté.', 'Built for teams that need clarity.') }}</x-site.display>
        <p class="ui:m-0 ui:mt-5 ui:text-lg ui:text-slate-600">{{ $t('Choisissez le profil qui correspond à votre organisation et à votre rôle.', 'Choose the view that matches your organization and security role.') }}</p>
        <div class="ui:mt-10 ui:grid ui:gap-3 ui:sm:grid-cols-2 ui:xl:grid-cols-4">
            @foreach($audiences as [$name, $text, $route])
                <a href="{{ route($routePrefix . $route) }}" class="ui:group ui:flex ui:min-h-52 ui:flex-col ui:gap-2.5 ui:rounded-2xl ui:bg-slate-100 ui:p-6 ui:text-ink! ui:no-underline! ui:hover:bg-slate-200">
                    <span class="ui:text-[32px] ui:font-black ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-125%">{{ $name }}</span>
                    <span class="ui:flex-1 ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</span>
                    <span class="ui:text-[22px] ui:font-bold ui:text-brand-500 ui:transition-transform ui:group-hover:translate-x-1">→</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Pentest --}}
    <section class="ui:bg-ink ui:text-white">
        <div class="{{ $wrap }} ui:grid ui:grid-cols-1 ui:items-end ui:gap-12 ui:py-24 ui:lg:grid-cols-2">
            <div>
                <x-site.display size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">{!! $t('Parfois, il faut un <span class="ui:text-brand-500">humain.</span>', 'Sometimes you need a <span class="ui:text-brand-500">human.</span>') !!}</x-site.display>
                <p class="ui:m-0 ui:mt-5 ui:text-[19px] ui:leading-normal ui:text-slate-400">{{ $t('Des tests experts pour les applications et infrastructures critiques.', 'Expert testing for critical applications and infrastructure.') }}</p>
                <x-site.button class="ui:mt-7" :href="route($routePrefix . 'solutions.pentest')">{{ $t('Réserver un pentest →', 'Book a pentest →') }}</x-site.button>
            </div>
            {{-- Spec sheet: label left, value right (tiles were too narrow for "1 RAPPORT") --}}
            <dl class="ui:m-0">
                @foreach($pentest as [$label, $value])
                    <div class="ui:flex ui:items-baseline ui:justify-between ui:gap-6 ui:border-0 ui:border-t ui:border-solid ui:border-slate-800 ui:py-5 {{ $loop->last ? 'ui:border-b' : '' }}">
                        <dt class="ui:font-mono ui:text-xs ui:uppercase ui:tracking-wider ui:text-slate-500">{{ $label }}</dt>
                        <dd class="ui:m-0 ui:text-[clamp(26px,3vw,40px)] ui:font-black ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-125%">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- Resources: hidden while the blog is empty --}}
    @if ($posts->isNotEmpty())
        <section class="{{ $wrap }} ui:pt-24">
            <div class="ui:flex ui:flex-wrap ui:items-end ui:justify-between ui:gap-4">
                <x-site.display>{{ $t('Depuis le lab Cywise.', 'From the Cywise lab.') }}</x-site.display>
                <a href="{{ route($isEnglish ? 'blog.en' : 'blog') }}" class="ui:font-bold ui:uppercase ui:text-ink! ui:no-underline! ui:hover:text-brand-700!">{{ $t('Tout voir →', 'View all →') }}</a>
            </div>
            <div class="ui:mt-10 ui:grid ui:gap-3 ui:md:grid-cols-3">
                @include('theme::partials.website-v2.posts-loop', ['posts' => $posts, 'locale' => $locale])
            </div>
        </section>
    @endif

    {{-- Ready --}}
    <section id="cta" class="{{ $wrap }} ui:py-28">
        <x-site.display size="xl" class="ui:text-[clamp(26px,5.6vw,92px)]!">{!! $t('Vous n’avez pas besoin de plus de complexité.<br><span class="ui:text-brand-500">Vous avez besoin de Cywise.</span>', 'You do not need more cybersecurity complexity.<br><span class="ui:text-brand-500">You need Cywise.</span>') !!}</x-site.display>
        <div class="ui:mt-10 ui:flex ui:flex-wrap ui:gap-3">
            <x-site.button :href="route('register')">{{ $t('Commencer gratuitement →', 'Start for free →') }}</x-site.button>
            <x-site.button variant="outline" :href="'mailto:' . config('towerify.freshdesk.from_email')">{{ $t('Demander une démo', 'Book a demo') }}</x-site.button>
        </div>
    </section>
</main>
