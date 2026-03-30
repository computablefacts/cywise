@php
    $tenant = auth()->user()?->tenant();
    $hasCustomLogo = $tenant?->hasCustomLogo() ?? false;
    $logoUrl = $tenant?->logoUrl() ?? asset('cywise/img/cywise.png');
    $logoLabel = $hasCustomLogo ? $tenant->name : 'Cywise';
@endphp

<img src="{{ $logoUrl }}"
     alt="{{ $logoLabel }}"
     title="{{ $logoLabel }}"
     {{ $attributes->merge(['class' => 'text-gray-900 dark:text-white']) }}>
