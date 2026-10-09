@php
    $isEnglish = $locale === 'en';
    $homeRoute = $isEnglish ? 'website.en.home' : 'home';
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
    $languageUrl = $languageUrl ?: route($isEnglish ? 'home' : 'website.en.home');

    // Menus shared by the desktop dropdowns and the mobile panel. "tone": the solution's colour (see site/edge-card).
    $menus = [
        [
            'label' => 'Solutions',
            'all' => [$isEnglish ? 'All solutions' : 'Toutes les solutions', route($routePrefix . 'solutions.index')],
            'items' => [
                ['critical', $isEnglish ? 'Attack surface' : 'Surface d’attaque', route($routePrefix . 'solutions.attack-surface')],
                ['medium', $isEnglish ? 'Vulnerability management' : 'Gestion des vulnérabilités', route($routePrefix . 'solutions.vulnerability-management')],
                ['low', $isEnglish ? 'Credential monitoring' : 'Surveillance des identifiants', route($routePrefix . 'solutions.credential-monitoring')],
                ['brand', 'CyberBuddy', route($routePrefix . 'solutions.cyberbuddy')],
                ['info', $isEnglish ? 'Security policy' : 'PSSI', route($routePrefix . 'solutions.pssi')],
                ['ink', 'Pentest', route($routePrefix . 'solutions.pentest')],
            ],
        ],
        [
            'label' => $isEnglish ? 'For whom' : 'Pour qui',
            'all' => [$isEnglish ? 'All audiences' : 'Tous les profils', route($routePrefix . 'audiences.index')],
            'items' => [
                [null, $isEnglish ? 'SMBs' : 'PME', route($routePrefix . 'audiences.smbs')],
                [null, 'Startups', route($routePrefix . 'audiences.startups')],
                [null, $isEnglish ? 'IT teams' : 'Équipes IT', route($routePrefix . 'audiences.it-teams')],
                [null, $isEnglish ? 'CISOs' : 'RSSI', route($routePrefix . 'audiences.cisos')],
                [null, 'MSP', route($routePrefix . 'audiences.msps')],
            ],
        ],
    ];
    $links = [
        [$isEnglish ? 'Use cases' : 'Cas d’usage', route($routePrefix . 'use-cases.index')],
        ['Blog', route($isEnglish ? 'blog.en' : 'blog')],
        [$isEnglish ? 'Pricing' : 'Tarifs', route($isEnglish ? 'website.en.pricing' : 'pricing')],
    ];

    // Literal class names: Tailwind only generates classes it finds in the sources
    $toneDot = [
        'critical' => 'ui:bg-critical',
        'medium' => 'ui:bg-medium',
        'low' => 'ui:bg-low',
        'brand' => 'ui:bg-brand-500',
        'info' => 'ui:bg-info',
        'ink' => 'ui:bg-slate-400',
    ];
    $link = 'ui:text-slate-700! ui:no-underline! ui:hover:text-ink!';
@endphp

{{--
  ┌ severity strip ───────────────────────────────────────────────┐
  │ CYWISE  Solutions ▾  Pour qui ▾  Cas d'usage  Blog  Tarifs   FR/EN  Se connecter  [Commencer] │  lg+
  │ CYWISE                                              [☰]       │  < lg: <details> panel
  └───────────────────────────────────────────────────────────────┘
--}}
<header class="ui:sticky ui:top-0 ui:z-50 ui:border-0 ui:border-b ui:border-solid ui:border-line ui:bg-white">
    <x-site.strip/>
    <nav class="ui:mx-auto ui:flex ui:max-w-[1240px] ui:items-center ui:gap-7 ui:px-4 ui:py-4 ui:sm:px-6">
        <a class="ui:font-display ui:text-[22px] ui:font-black ui:tracking-tight ui:text-ink! ui:no-underline! ui:font-stretch-125%" href="{{ route($homeRoute) }}">CYWISE</a>

        {{-- Desktop --}}
        <div class="ui:hidden ui:flex-1 ui:items-center ui:gap-6 ui:text-[15px] ui:font-medium ui:lg:flex">
            @foreach($menus as $menu)
                <div class="ui:group ui:relative">
                    <a href="{{ $menu['all'][1] }}" class="ui:flex ui:items-center ui:gap-1 ui:py-1.5 {{ $link }}">
                        {{ $menu['label'] }}
                        <x-phosphor-caret-down-bold class="ui:size-3"/>
                    </a>
                    <div class="ui:invisible ui:absolute ui:left-0 ui:top-full ui:pt-3 ui:opacity-0 ui:transition-opacity ui:group-hover:visible ui:group-hover:opacity-100 ui:group-focus-within:visible ui:group-focus-within:opacity-100">
                        <ul class="ui:m-0 ui:flex ui:w-72 ui:list-none ui:flex-col ui:gap-1 ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-2 ui:shadow-lg">
                            @foreach($menu['items'] as [$tone, $label, $url])
                                <li>
                                    <a href="{{ $url }}" class="ui:flex ui:items-center ui:gap-3 ui:rounded-xl ui:px-3 ui:py-2.5 ui:hover:bg-slate-50 {{ $link }}">
                                        @if($tone)
                                            <span class="ui:h-4 ui:w-1 ui:rounded-full {{ $toneDot[$tone] }}" aria-hidden="true"></span>
                                        @endif
                                        {{ $label }}
                                    </a>
                                </li>
                            @endforeach
                            <li class="ui:border-0 ui:border-t ui:border-solid ui:border-line ui:pt-1">
                                <a href="{{ $menu['all'][1] }}" class="ui:flex ui:rounded-xl ui:px-3 ui:py-2.5 ui:font-semibold ui:text-brand-700! ui:no-underline! ui:hover:bg-brand-50">{{ $menu['all'][0] }} →</a>
                            </li>
                        </ul>
                    </div>
                </div>
            @endforeach
            @foreach($links as [$label, $url])
                <a href="{{ $url }}" class="ui:py-1.5 {{ $link }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="ui:hidden ui:items-center ui:gap-5 ui:lg:flex">
            <div aria-label="{{ $isEnglish ? 'Language selector' : 'Sélecteur de langue' }}" class="ui:flex ui:gap-1.5 ui:font-mono ui:text-[13px]">
                <a class="ui:no-underline! {{ $isEnglish ? 'ui:text-slate-400!' : 'ui:font-medium ui:text-ink!' }}" href="{{ $isEnglish ? $languageUrl : url()->current() }}" lang="fr">FR</a>
                <span class="ui:text-slate-300">/</span>
                <a class="ui:no-underline! {{ $isEnglish ? 'ui:font-medium ui:text-ink!' : 'ui:text-slate-400!' }}" href="{{ $isEnglish ? url()->current() : $languageUrl }}" lang="en">EN</a>
            </div>
            <a href="{{ route('login') }}" class="ui:text-[15px] ui:font-semibold {{ $link }}">{{ $isEnglish ? 'Sign in' : 'Se connecter' }}</a>
            <x-site.button size="md" :href="route('register')">{{ $isEnglish ? 'Get started →' : 'Commencer →' }}</x-site.button>
        </div>

        {{-- Mobile: native disclosure, no JS --}}
        <details class="ui:group ui:ml-auto ui:lg:hidden">
            <summary class="ui:flex ui:size-11 ui:cursor-pointer ui:list-none ui:items-center ui:justify-center ui:rounded-xl ui:border ui:border-solid ui:border-line ui:[&::-webkit-details-marker]:hidden"
                     aria-label="{{ $isEnglish ? 'Open navigation' : 'Ouvrir la navigation' }}">
                <x-phosphor-list-bold class="ui:size-5 ui:group-open:hidden"/>
                <x-phosphor-x-bold class="ui:hidden ui:size-5 ui:group-open:block"/>
            </summary>
            <div class="ui:fixed ui:inset-x-0 ui:bottom-0 ui:top-[78px] ui:hidden ui:flex-col ui:group-open:flex ui:overflow-y-auto ui:bg-ink ui:px-5 ui:pb-6 ui:text-white">
                @foreach($menus as $menu)
                    <details class="ui:group/menu ui:border-0 ui:border-b ui:border-solid ui:border-slate-800 ui:py-3.5" @if($loop->first) open @endif>
                        <summary class="ui:flex ui:cursor-pointer ui:list-none ui:items-center ui:justify-between ui:font-display ui:text-[30px] ui:font-black ui:uppercase ui:tracking-tight ui:font-stretch-125% ui:[&::-webkit-details-marker]:hidden">
                            {{ $menu['label'] }}
                            <x-phosphor-plus-bold class="ui:size-5 ui:text-slate-500 ui:group-open/menu:hidden"/>
                            <x-phosphor-minus-bold class="ui:hidden ui:size-5 ui:text-brand-500 ui:group-open/menu:block"/>
                        </summary>
                        <div class="ui:mt-3 ui:flex ui:flex-col ui:gap-2.5 ui:text-base">
                            @foreach($menu['items'] as [$tone, $label, $url])
                                <a href="{{ $url }}" class="ui:flex ui:items-center ui:gap-2.5 ui:text-slate-300! ui:no-underline!">
                                    @if($tone)
                                        <span class="ui:h-4 ui:w-1 ui:rounded-full {{ $toneDot[$tone] }}" aria-hidden="true"></span>
                                    @endif
                                    {{ $label }}
                                </a>
                            @endforeach
                            <a href="{{ $menu['all'][1] }}" class="ui:text-orange-400! ui:no-underline!">{{ $menu['all'][0] }} →</a>
                        </div>
                    </details>
                @endforeach
                @foreach($links as [$label, $url])
                    <a href="{{ $url }}" class="ui:border-0 ui:border-b ui:border-solid ui:border-slate-800 ui:py-3.5 ui:font-display ui:text-[30px] ui:font-black ui:uppercase ui:tracking-tight ui:text-white! ui:no-underline! ui:font-stretch-125%">{{ $label }}</a>
                @endforeach
                <div class="ui:mt-auto ui:flex ui:flex-col ui:gap-3.5 ui:pt-6">
                    <div class="ui:flex ui:gap-2 ui:font-mono ui:text-sm">
                        <a class="ui:no-underline! {{ $isEnglish ? 'ui:text-slate-400!' : 'ui:text-white!' }}" href="{{ $isEnglish ? $languageUrl : url()->current() }}" lang="fr">FR</a>
                        <span class="ui:text-slate-600">/</span>
                        <a class="ui:no-underline! {{ $isEnglish ? 'ui:text-white!' : 'ui:text-slate-400!' }}" href="{{ $isEnglish ? url()->current() : $languageUrl }}" lang="en">EN</a>
                    </div>
                    <x-site.button variant="light" :href="route('login')">{{ $isEnglish ? 'Sign in' : 'Se connecter' }}</x-site.button>
                    <x-site.button :href="route('register')">{{ $isEnglish ? 'Get started →' : 'Commencer →' }}</x-site.button>
                </div>
            </div>
        </details>
    </nav>
</header>
