@php
    $isEnglish = $locale === 'en';
    $plans = app(\App\Services\PricingContent::class)->plans();
@endphp

@foreach ($plans as $plan)
    @php
        $features = array_filter(array_map('trim', explode(',', $plan->features ?? '')));
    @endphp

    <div class="col-lg-4">
        <article class="pricing-plan">
            <span class="mono">{{ $isEnglish ? 'SUBSCRIPTION' : 'ABONNEMENT' }}</span>
            <h2>{{ $plan->name }}</h2>
            <div class="price">
                {{ $plan->currency }}{{ $plan->monthly_price }}
                <small>/ {{ $isEnglish ? 'MONTH' : 'MOIS' }}</small>
            </div>

            @if ($plan->yearly_price)
                <div class="annual-price">
                    {{ $plan->currency }}{{ $plan->yearly_price }} / {{ $isEnglish ? 'YEAR' : 'AN' }}
                </div>
            @endif

            <p>{{ $plan->description }}</p>

            @if ($features !== [])
                <ul>
                    @foreach ($features as $feature)
                        <li>{{ strip_tags($feature) }}</li>
                    @endforeach
                </ul>
            @endif

            <a class="btn btn-dark-brutal w-100" href="{{ route('settings.subscription') }}">
                {{ $isEnglish ? 'SELECT →' : 'CHOISIR →' }}
            </a>
        </article>
    </div>
@endforeach

<div class="col-lg-4">
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
