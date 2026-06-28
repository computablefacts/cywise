@props([
    'title' => '',
    'type' => 'gray', // info, success, warning, danger
    'id' => uniqid(),
    'dismissable' => true
])

@php

    $alertIcon = 'phosphor-info-duotone';

    $alertIcon = match($type)
    {
        'info' => 'phosphor-info-duotone',
        'success' => 'icon-check-circle-duotone',
        'warning' => 'icon-warning-duotone',
        'danger' => 'icon-warning-circle-duotone',
        'gray' => 'icon-info-duotone'
    };


@endphp

<div
    x-show="alert_{{ $id }}"
    x-data="{ alert_{{ $id }}: $persist(true) }"
    {{ $attributes->class([
        'alert position-relative ps-3 pe-5 py-3 w-100 rounded border',
        'bg-light text-dark border-secondary' => $type == 'gray',
        'alert-info' => $type == 'info',
        'alert-success' => $type == 'success',
        'alert-warning' => $type == 'warning',
        'alert-danger' => $type == 'danger'
    ]) }}
    x-collapse
    x-cloak
>
    @if($dismissable)
        <button @click="alert_{{ $id }}=false" class="btn-close position-absolute end-0 top-0 m-2" aria-label="Close"></button>
    @endif
    @if($title ?? false)
        <div class="d-flex align-items-start gap-2">
            <x-icon name="{{ $alertIcon }}" class="flex-shrink-0" style="width: 1.25rem; height: 1.25rem;" />
            <h5 class="mb-1 fw-bold">{{ $title }}</h5>
        </div>
    @endif
    <div class="@if($title ?? false){{ 'ps-4 ms-2' }}@endif small">{{ $slot }}</div>

</div>