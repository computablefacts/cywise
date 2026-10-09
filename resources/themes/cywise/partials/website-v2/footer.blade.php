@php
    $isEnglish = $locale === 'en';
    $homeRoute = $isEnglish ? 'website.en.home' : 'home';
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';

    $columns = [
        'Solutions' => [
            [$isEnglish ? 'Attack surface' : 'Surface d’attaque', route($routePrefix . 'solutions.attack-surface')],
            [$isEnglish ? 'Vulnerabilities' : 'Vulnérabilités', route($routePrefix . 'solutions.vulnerability-management')],
            ['CyberBuddy', route($routePrefix . 'solutions.cyberbuddy')],
            ['Pentest', route($routePrefix . 'solutions.pentest')],
        ],
        ($isEnglish ? 'For whom' : 'Pour qui') => [
            [$isEnglish ? 'SMBs' : 'PME', route($routePrefix . 'audiences.smbs')],
            [$isEnglish ? 'IT teams' : 'Équipes IT', route($routePrefix . 'audiences.it-teams')],
            [$isEnglish ? 'CISOs' : 'RSSI', route($routePrefix . 'audiences.cisos')],
            ['MSP', route($routePrefix . 'audiences.msps')],
        ],
        ($isEnglish ? 'Resources' : 'Ressources') => [
            ['Blog', route($isEnglish ? 'blog.en' : 'blog')],
            [$isEnglish ? 'Use cases' : 'Cas d’usage', route($routePrefix . 'use-cases.index')],
            [$isEnglish ? 'Pricing' : 'Tarifs', route($isEnglish ? 'website.en.pricing' : 'pricing')],
        ],
        'Cywise' => [
            [$isEnglish ? 'Home' : 'Accueil', route($homeRoute)],
            ['Contact', route($homeRoute) . '#cta'],
            [$isEnglish ? 'Privacy' : 'Confidentialité', route('privacy-policy')],
        ],
    ];
@endphp

<footer class="ui:bg-ink ui:text-slate-200">
    <div class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-8 ui:pt-16 ui:sm:px-6">
        <div class="ui:grid ui:grid-cols-2 ui:gap-10 ui:md:grid-cols-6">
            <div class="ui:col-span-2">
                <a href="{{ route($homeRoute) }}" class="ui:font-display ui:text-[40px] ui:font-black ui:tracking-tight ui:text-white! ui:no-underline! ui:font-stretch-125%">CYWISE</a>
                <p class="ui:m-0 ui:mt-3 ui:font-display ui:text-xl ui:font-extrabold ui:uppercase ui:leading-tight ui:text-brand-500 ui:font-stretch-112%">
                    {{ $isEnglish ? 'Cybersecurity for everyone.' : 'La cybersécurité pour tous.' }}
                </p>
            </div>
            @foreach($columns as $title => $items)
                <div class="ui:flex ui:flex-col ui:gap-2.5 ui:text-[15px]">
                    <span class="ui:font-mono ui:text-xs ui:uppercase ui:tracking-widest ui:text-slate-500">{{ $title }}</span>
                    @foreach($items as [$label, $url])
                        <a href="{{ $url }}" class="ui:text-slate-300! ui:no-underline! ui:hover:text-white!">{{ $label }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
        <div class="ui:mt-14 ui:border-0 ui:border-t ui:border-solid ui:border-slate-800 ui:pt-5 ui:font-mono ui:text-xs ui:tracking-wider ui:text-slate-500">
            © {{ now()->year }} CYWISE
        </div>
    </div>
    <x-site.strip reverse/>
</footer>
