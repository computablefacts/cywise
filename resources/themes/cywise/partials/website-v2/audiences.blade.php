@php
    // Audiences index, shared by pages/for-whom/index (fr) and pages/en/for-whom/index (en).
    $isEnglish = $locale === 'en';
    $t = fn (string $fr, string $en) => $isEnglish ? $en : $fr;
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
    $wrap = 'ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:sm:px-6';

    $audiences = [
        [$t('PME', 'SMBs'), $t('Sécurisez votre activité avec une solution simple et efficace sans infrastructure complexe.', 'Security for small and medium businesses.'), 'audiences.smbs'],
        ['Startups', $t('Bâtissez des fondations de sécurité solides pour accompagner votre forte croissance.', 'Security foundations for fast-growing teams.'), 'audiences.startups'],
        [$t('Équipes IT', 'IT teams'), $t('Automatisez votre surveillance et visualisez exactement les actions à mener.', 'Prioritized security work for IT operations.'), 'audiences.it-teams'],
        [$t('RSSI', 'CISOs'), $t('Centralisez votre visibilité et facilitez vos prises de décisions stratégiques.', 'Clear visibility for security leadership.'), 'audiences.cisos'],
        [$t('MSP', 'MSPs'), $t('Gérez la posture de sécurité de tous vos clients depuis une plateforme unique.', 'Repeatable security visibility for customers.'), 'audiences.msps'],
    ];
@endphp

<main>
    {{-- Hero --}}
    <section class="{{ $wrap }} ui:pb-16 ui:pt-16">
        <x-site.display as="h1" size="xl" class="ui:max-w-[1100px] ui:text-[clamp(26px,5.6vw,92px)]!">{!! $t('Conçu pour les équipes qui exigent de la <span class="ui:text-brand-500">clarté.</span>', 'Built for teams that need <span class="ui:text-brand-500">clarity.</span>') !!}</x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">{{ $t('Sélectionnez le profil qui correspond à votre organisation pour découvrir votre expérience personnalisée.', 'Choose the experience that matches your organization or role.') }}</p>
    </section>

    {{-- Audience cards, same style as the home page's "for whom" section --}}
    <section class="{{ $wrap }} ui:pb-24">
        <div class="ui:grid ui:grid-cols-1 ui:gap-3 ui:sm:grid-cols-2 ui:lg:grid-cols-3">
            @foreach($audiences as [$name, $text, $route])
                <a href="{{ route($routePrefix . $route) }}" class="ui:group ui:flex ui:min-h-52 ui:flex-col ui:gap-2.5 ui:rounded-2xl ui:bg-slate-100 ui:p-6 ui:text-ink! ui:no-underline! ui:hover:bg-slate-200">
                    <span class="ui:text-[32px] ui:font-black ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-125%">{{ $name }}</span>
                    <span class="ui:flex-1 ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</span>
                    <span class="ui:text-[22px] ui:font-bold ui:text-brand-500 ui:transition-transform ui:group-hover:translate-x-1">→</span>
                </a>
            @endforeach
        </div>
    </section>

    <x-site.cta :href="route('register')" :label="$t('Démarrer maintenant →', 'Start protecting →')">
        <x-slot:title>{!! $t('Démystifiez votre<br>cybersécurité.', 'Make your<br>security visible.') !!}</x-slot:title>
    </x-site.cta>
</main>
