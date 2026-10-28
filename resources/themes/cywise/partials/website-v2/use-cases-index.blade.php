@php
    // Use-case index, shared by pages/use-cases/index (fr) and pages/en/use-cases/index (en).
    $isEnglish = $locale === 'en';
    $t = fn (string $fr, string $en) => $isEnglish ? $en : $fr;
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
    $wrap = 'ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:sm:px-6';

    // Edge = the matching solution's tone (see site/edge-card); an audit spans all solutions → neutral line
    $cases = [
        ['ui:bg-medium', $t('Trouver vos vulnérabilités', 'Find your vulnerabilities'), $t('Détectez et priorisez vos failles de sécurité.', 'Detect and prioritize your security flaws.'), 'use-cases.find-vulnerabilities'],
        ['ui:bg-critical', $t('Surveiller votre surface d’attaque', 'Monitor your attack surface'), $t('Maîtrisez votre exposition externe en continu.', 'Master your external exposure continuously.'), 'use-cases.monitor-attack-surface'],
        ['ui:bg-low', $t('Rechercher vos identifiants compromis', 'Search for your compromised credentials'), $t('Identifiez vos données collaborateurs exposées.', 'Identify your exposed employee data.'), 'use-cases.check-leaked-credentials'],
        ['ui:bg-line', $t('Préparer votre audit', 'Prepare your audit'), $t('Centralisez vos preuves de sécurité et corrigez vos écarts.', 'Centralize your security evidence and fix your gaps.'), 'use-cases.prepare-audit'],
        ['ui:bg-ink', $t('Lancer un pentest expert', 'Launch an expert pentest'), $t('Évaluez vos systèmes critiques avec nos experts.', 'Evaluate your critical systems with our experts.'), 'use-cases.run-pentest'],
    ];
@endphp

<main>
    <section class="{{ $wrap }} ui:pb-16 ui:pt-16">
        <x-site.display as="h1" size="xl">{{ $t('Quel est votre objectif prioritaire ?', 'What is your priority objective?') }}</x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">{{ $t('Sélectionnez votre enjeu sécurité. Cywise vous guide avec un flux de travail clair et des actions concrètes.', 'Select your security challenge. Cywise guides you with a clear workflow and concrete actions.') }}</p>
    </section>

    {{-- Signage list: one row per use case, colour edge thickens on hover --}}
    <section class="{{ $wrap }} ui:pb-28">
        <ul class="ui:m-0 ui:list-none ui:border-0 ui:border-t ui:border-solid ui:border-line ui:p-0">
            @foreach($cases as [$edge, $title, $text, $route])
                <li>
                    <a href="{{ route($routePrefix . $route) }}" class="ui:group ui:flex ui:items-stretch ui:gap-4 ui:border-0 ui:border-b ui:border-solid ui:border-line ui:py-6 ui:text-ink! ui:no-underline! ui:sm:gap-6 ui:sm:py-8">
                        <span class="ui:w-2.5 ui:shrink-0 ui:rounded-sm ui:transition-all ui:group-hover:w-4 {{ $edge }}" aria-hidden="true"></span>
                        <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-2 ui:lg:flex-row ui:lg:items-center ui:lg:justify-between ui:lg:gap-10">
                            <x-site.display as="span" size="md" class="ui:block ui:text-[clamp(24px,4.4vw,56px)]! ui:transition-colors ui:group-hover:text-brand-600">{{ $title }}</x-site.display>
                            <span class="ui:text-base ui:leading-normal ui:text-slate-600 ui:lg:max-w-[300px] ui:lg:shrink-0">{{ $text }}</span>
                        </span>
                        <span class="ui:self-center ui:text-[clamp(24px,3.5vw,44px)] ui:font-bold ui:leading-none ui:transition-transform ui:group-hover:translate-x-1.5" aria-hidden="true">→</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
</main>
