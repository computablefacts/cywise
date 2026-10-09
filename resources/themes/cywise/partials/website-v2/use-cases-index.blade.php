@php
    // Use-case index, shared by pages/use-cases/index (fr) and pages/en/use-cases/index (en).
    $isEnglish = $locale === 'en';
    $t = fn (string $fr, string $en) => $isEnglish ? $en : $fr;
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
    $wrap = 'ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:sm:px-6';

    // Edge = the matching solution's tone (see site/edge-card); an audit spans all solutions → neutral line
    $cases = [
        ['ui:bg-medium', $t('Trouver des vulnérabilités', 'Find vulnerabilities'), $t('Détectez et priorisez les faiblesses.', 'Discover and prioritize weaknesses.'), 'use-cases.find-vulnerabilities'],
        ['ui:bg-critical', $t('Surveiller la surface d’attaque', 'Monitor attack surface'), $t('Suivez l’exposition publique dans le temps.', 'Track public exposure over time.'), 'use-cases.monitor-attack-surface'],
        ['ui:bg-low', $t('Vérifier les identifiants compromis', 'Check leaked credentials'), $t('Détectez les identités compromises de l’entreprise.', 'Detect compromised company identities.'), 'use-cases.check-leaked-credentials'],
        ['ui:bg-info', $t('Créer une PSSI', 'Create a PSSI'), $t('Créez une politique de sécurité pragmatique.', 'Build a practical security policy.'), 'use-cases.create-pssi'],
        ['ui:bg-line', $t('Préparer un audit', 'Prepare for an audit'), $t('Organisez les preuves de sécurité et les écarts.', 'Organize security evidence and gaps.'), 'use-cases.prepare-audit'],
        ['ui:bg-ink', $t('Lancer un pentest', 'Run a pentest'), $t('Éprouvez les systèmes critiques avec des experts.', 'Challenge critical systems with experts.'), 'use-cases.run-pentest'],
    ];
@endphp

<main>
    <section class="{{ $wrap }} ui:pb-16 ui:pt-16">
        <x-site.display as="h1" size="xl">{{ $t('Qu’avez-vous besoin de faire ?', 'What do you need to do?') }}</x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[520px] ui:text-xl ui:leading-normal ui:text-slate-700">{{ $t('Partez du problème. Cywise le transforme en flux de sécurité clair.', 'Start with the problem. Cywise maps it to a clear security workflow.') }}</p>
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
