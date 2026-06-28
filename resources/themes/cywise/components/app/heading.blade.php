@props([
    'title' => '',
    'description' => '',
    'border' => true
])

<div class="@if($border){{ 'pb-3 mb-3 border-bottom' }}@endif">
    <h3 class="h5 fw-bold mb-1">{{ $title ?? '' }}</h3>
    <p class="small text-muted mb-0">{{ $description ?? '' }}</p>
</div>