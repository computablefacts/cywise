@php
    $tenant = auth()->user()?->tenant();
    $customLogoUrl = $tenant?->customLogoUrl();
    $logoUrl = $customLogoUrl ?? asset('cywise/img/cywise.png');
    $logoLabel = $customLogoUrl ? $tenant->name : 'Cywise';
@endphp

<img src="{{ $logoUrl }}"
     alt="{{ $logoLabel }}"
     title="{{ $logoLabel }}"
     {{ $attributes->merge(['class' => 'text-gray-900 dark:text-white']) }}>
