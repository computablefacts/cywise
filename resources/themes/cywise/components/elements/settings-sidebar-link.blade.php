<a href="{{ $href }}" class="@if($href == RalphJSmit\Livewire\Urls\Facades\Url::current()){{ 'bg-light text-dark fw-bold' }}@else{{ 'text-muted fw-medium' }}@endif d-flex justify-content-start align-items-center ps-3 pe-4 py-2 small text-nowrap rounded transition-all position-relative text-decoration-none border-0 w-100" onmouseover="this.classList.add('bg-light')" onmouseout="@if($href != RalphJSmit\Livewire\Urls\Facades\Url::current()) this.classList.remove('bg-light') @endif">
    <span class="position-absolute start-0 h-75 translate-middle-y top-50 rounded-pill transition-all @if($href == RalphJSmit\Livewire\Urls\Facades\Url::current()){{ 'd-block' }}@else{{ 'd-none' }}@endif" style="width: 3px; background:{{ config('wave.primary_color') }}"></span>
    <div class="d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 1.25rem; height: 1.25rem;">
        <x-dynamic-component :component="$icon" class="w-100 h-100" />
    </div>
    <span class="d-none d-md-inline-block text-truncate">{{ $slot }}</span>
</a>
