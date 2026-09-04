@php
    $isEnglish = $locale === 'en';
@endphp

<div class="col-lg-5">
    <article class="pricing-plan">
        <span class="mono">{{ $isEnglish ? 'PLATFORM' : 'PLATEFORME' }}</span>
        <h2>CYWISE</h2>
        <div class="price">{{ $isEnglish ? 'CUSTOM' : 'SUR MESURE' }}</div>
        <p>{{ $isEnglish ? 'Continuous security visibility for your organization.' : 'Une visibilité sécurité continue pour votre organisation.' }}</p>
        <ul>
            <li>{{ $isEnglish ? 'Attack surface monitoring' : 'Surveillance de la surface d’attaque' }}</li>
            <li>{{ $isEnglish ? 'Vulnerability management' : 'Gestion des vulnérabilités' }}</li>
            <li>{{ $isEnglish ? 'Credential monitoring' : 'Surveillance des identifiants' }}</li>
            <li>CyberBuddy</li>
            <li>{{ $isEnglish ? 'PSSI support' : 'Assistance PSSI' }}</li>
        </ul>
        <a class="btn btn-dark-brutal w-100" href="{{ route('register') }}">
            {{ $isEnglish ? 'START →' : 'COMMENCER →' }}
        </a>
    </article>
</div>

<div class="col-lg-5">
    <article class="pricing-plan pricing-plan-dark">
        <span class="mono">{{ $isEnglish ? 'EXPERT SERVICE' : 'SERVICE EXPERT' }}</span>
        <h2>PENTEST</h2>
        <div class="price">{{ $isEnglish ? '€3,000+' : '3 000 €+' }}</div>
        <p>{{ $isEnglish ? 'Human testing for critical applications and infrastructure.' : 'Des tests humains pour les applications et infrastructures critiques.' }}</p>
        <ul>
            <li>{{ $isEnglish ? 'Defined scope' : 'Périmètre défini' }}</li>
            <li>{{ $isEnglish ? 'Expert manual testing' : 'Tests manuels par des experts' }}</li>
            <li>{{ $isEnglish ? 'Prioritized report' : 'Rapport priorisé' }}</li>
            <li>{{ $isEnglish ? 'Remediation guidance' : 'Conseils de remédiation' }}</li>
            <li>{{ $isEnglish ? 'Retest path' : 'Parcours de contre-test' }}</li>
        </ul>
        <a
            class="btn btn-acid w-100"
            href="{{ route($isEnglish ? 'website.en.solutions.pentest' : 'website.solutions.pentest') }}"
        >
            {{ $isEnglish ? 'BOOK A PENTEST →' : 'RÉSERVER UN PENTEST →' }}
        </a>
    </article>
</div>
