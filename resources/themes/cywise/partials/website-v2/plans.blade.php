@php
    $isEnglish = $locale === 'en';
    $plans = app(\App\Services\PricingContent::class)->plans();

    // Featured plan (the DB default, i.e. the middle one): brand edge + ink border
    $featuredBorder = 'ui:border-2! ui:border-ink!';
    $check = 'ui:mt-1 ui:size-4 ui:shrink-0';
@endphp

{{-- One card per plan, then the pentest panel; the parent provides the grid. --}}
@foreach ($plans as $plan)
    @php
        $features = array_filter(array_map('trim', explode(',', $plan->features ?? '')));
        $isFeatured = (bool) $plan->default;
    @endphp

    <x-site.edge-card :tone="$isFeatured ? 'brand' : 'line'" class="{{ $isFeatured ? $featuredBorder : '' }}">
        <h2 class="ui:m-0 ui:text-[28px] ui:font-black ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-118%">{{ $plan->name }}</h2>

        <p class="ui:m-0 ui:mt-3 ui:flex ui:flex-wrap ui:items-baseline ui:gap-x-2">
            <span class="ui:text-[44px] ui:font-black ui:leading-none ui:tracking-tight ui:font-stretch-125%">{{ $plan->currency }}{{ $plan->monthly_price }}</span>
            <span class="ui:font-mono ui:text-xs ui:uppercase ui:text-slate-500">/ {{ $isEnglish ? 'month' : 'mois' }}</span>
        </p>

        @if ($plan->yearly_price)
            <p class="ui:m-0 ui:font-mono ui:text-[13px] ui:uppercase ui:text-slate-500">
                {{ $plan->currency }}{{ $plan->yearly_price }} / {{ $isEnglish ? 'year' : 'an' }}
            </p>
        @endif

        <p class="ui:m-0 ui:mt-2 ui:text-base ui:leading-normal ui:text-slate-600">{{ $plan->description }}</p>

        @if ($features !== [])
            <ul class="ui:m-0 ui:mt-3 ui:flex ui:list-none ui:flex-col ui:gap-2.5 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:p-0 ui:pt-5">
                @foreach ($features as $feature)
                    <li class="ui:flex ui:gap-2.5 ui:text-[15px] ui:leading-snug">
                        <x-phosphor-check-bold class="{{ $check }} ui:text-low" aria-hidden="true"/>
                        {{ strip_tags($feature) }}
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="ui:mt-auto ui:pt-6">
            <x-site.button :variant="$isFeatured ? 'primary' : 'dark'" size="md" class="ui:w-full" :href="route('settings.subscription')">
                {{ $isEnglish ? 'Select →' : 'Choisir →' }}
            </x-site.button>
        </div>
    </x-site.edge-card>
@endforeach

{{-- Pentest: one-off expert service, ink panel (pentest tone, see site/edge-card) --}}
<article class="ui:flex ui:flex-col ui:gap-2 ui:rounded-2xl ui:bg-ink ui:p-6 ui:pl-8 ui:text-white">
    <h2 class="ui:m-0 ui:text-[28px] ui:font-black ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:font-stretch-118%">Pentest</h2>

    <p class="ui:m-0 ui:mt-3 ui:text-[44px] ui:font-black ui:leading-none ui:tracking-tight ui:font-stretch-125%">{{ $isEnglish ? '€3,000+' : '3 000 €+' }}</p>

    <p class="ui:m-0 ui:mt-2 ui:text-base ui:leading-normal ui:text-slate-400">{{ $isEnglish ? 'Human testing for critical applications and infrastructure.' : 'Des tests humains pour les applications et infrastructures critiques.' }}</p>

    <ul class="ui:m-0 ui:mt-3 ui:flex ui:list-none ui:flex-col ui:gap-2.5 ui:border-0 ui:border-t ui:border-solid ui:border-slate-800 ui:p-0 ui:pt-5">
        @foreach ([
            $isEnglish ? 'Defined scope' : 'Périmètre défini',
            $isEnglish ? 'Expert manual testing' : 'Tests manuels par des experts',
            $isEnglish ? 'Prioritized report' : 'Rapport priorisé',
            $isEnglish ? 'Remediation guidance' : 'Conseils de remédiation',
            $isEnglish ? 'Retest path' : 'Parcours de contre-test',
        ] as $item)
            <li class="ui:flex ui:gap-2.5 ui:text-[15px] ui:leading-snug">
                <x-phosphor-check-bold class="{{ $check }} ui:text-brand-500" aria-hidden="true"/>
                {{ $item }}
            </li>
        @endforeach
    </ul>

    <div class="ui:mt-auto ui:pt-6">
        <x-site.button size="md" class="ui:w-full" :href="route($isEnglish ? 'website.en.solutions.pentest' : 'website.solutions.pentest')">
            {{ $isEnglish ? 'Book a pentest →' : 'Réserver un pentest →' }}
        </x-site.button>
    </div>
</article>
