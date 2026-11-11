@php
    // Use-case index, shared by pages/use-cases/index (fr) and pages/en/use-cases/index (en).
    $isEnglish = $locale === 'en';
    $t = fn (string $fr, string $en) => $isEnglish ? $en : $fr;
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
    $wrap = 'ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:sm:px-6';

    // Tone = the matching solution's tone (see site/edge-card)
    $cases = [
        ['critical', $t('Surveiller votre surface d’attaque', 'Monitor your attack surface'), $t('Maîtrisez votre exposition externe en continu.', 'Master your external exposure continuously.'), 'use-cases.monitor-attack-surface'],
        ['medium', $t('Trouver vos vulnérabilités', 'Find your vulnerabilities'), $t('Détectez et priorisez vos failles de sécurité.', 'Detect and prioritize your security flaws.'), 'use-cases.find-vulnerabilities'],
        ['low', $t('Rechercher vos identifiants compromis', 'Search for your compromised credentials'), $t('Identifiez vos données collaborateurs exposées.', 'Identify your exposed employee data.'), 'use-cases.check-leaked-credentials'],
        ['info', $t('Préparer votre audit', 'Prepare your audit'), $t('Centralisez vos preuves de sécurité et corrigez vos écarts.', 'Centralize your security evidence and fix your gaps.'), 'use-cases.prepare-audit'],
        ['ink', $t('Lancer un pentest expert', 'Launch an expert pentest'), $t('Évaluez vos systèmes critiques avec nos experts.', 'Evaluate your critical systems with our experts.'), 'use-cases.run-pentest'],
    ];
@endphp

<main>
    <section class="{{ $wrap }} ui:pb-16 ui:pt-16">
        <x-site.display as="h1" size="xl">
            {!! $t('Quel est votre <span class="ui:text-brand-500">objectif prioritaire ?</span>', 'What is your <span class="ui:text-brand-500">priority objective?</span>') !!}
        </x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">{{ $t('Sélectionnez votre enjeu sécurité. Cywise vous guide avec un processus clair et des actions concrètes.', 'Select your security challenge. Cywise guides you with a clear process and concrete actions.') }}</p>
    </section>

    {{-- Grid of use case cards, same as home's solutions --}}
    <section class="{{ $wrap }} ui:pb-28">
        <div class="ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-2 ui:xl:grid-cols-3">
            @foreach($cases as [$tone, $title, $text, $route])
                @php
                    $textColor = match ($tone) {
                        'critical' => 'ui:text-critical',
                        'medium' => 'ui:text-medium',
                        'low' => 'ui:text-low',
                        'info' => 'ui:text-info',
                        'ink' => 'ui:text-ink',
                        default => 'ui:text-brand-500',
                    };
                @endphp
                <x-site.edge-card :tone="$tone" :href="route($routePrefix . $route)" variant="flat">
                    <span class="ui:text-2xl ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-112%">{{ $title }}</span>
                    <span class="ui:flex-1 ui:text-base ui:leading-normal ui:text-slate-600">{{ $text }}</span>
                    <span class="ui:mt-2 ui:text-sm ui:font-bold ui:uppercase {{ $textColor }} ui:transition-transform ui:group-hover:translate-x-1">
                        {{ $t('Découvrir →', 'Explore →') }}
                    </span>
                </x-site.edge-card>
            @endforeach
        </div>
    </section>
</main>
