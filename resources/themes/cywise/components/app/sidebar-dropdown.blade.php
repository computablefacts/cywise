<div x-data="{ {{ $id }}: {{ $open ?? false }} }"
    :class="{ 'bg-light border rounded' : {{ $id }} == true }"
    class="position-relative w-100 select-none">
    <div
        @click="{{ $id }}=!{{ $id }}"
        class="@if($active){{ 'text-dark bg-white border shadow-sm fw-medium' }}@endif transition-colors d-flex rounded w-100 px-3 py-2 cursor-pointer small border justify-content-start align-items-center overflow-hidden"
        :class="{ 'text-dark bg-white border shadow-sm fw-medium' : {{ $id }} == true, 'hover-bg-light border-transparent' : ({{ $id }} == false && {{ !$active }}) }"
    >
        <div class="d-flex position-relative align-items-center w-100">
            <x-dynamic-component :component="$icon" class="flex-shrink-0 me-2" style="width: 1.25rem; height: 1.25rem;" />
            <span>{{ $text }}</span>
            <span :class="{ 'rotate-180' : {{ $id }} == true }" class="ms-auto transition-transform" style="width: 1rem; height: 1rem;">
                <x-phosphor-caret-down class="w-100 h-100" />
            </span>
        </div>

        <template x-teleport="#{{ $id }}">
            <div class="position-relative p-1 d-grid gap-1" x-show="{{ $id }}" x-collapse x-cloak>
                {{ $slot }}
            </div>
        </template>
    </div>

    <div id="{{ $id }}"></div>

</div>
