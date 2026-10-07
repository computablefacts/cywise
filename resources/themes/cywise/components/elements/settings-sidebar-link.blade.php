@props([
    'href' => '',
    'icon' => 'phosphor-house',
])

{{-- Settings nav entry: an app sidebar link; the wrapper keeps it on one line in the mobile horizontal nav --}}
<div class="ui:shrink-0">
    <x-app.sidebar-link :href="$href" :icon="$icon" :active="$href == RalphJSmit\Livewire\Urls\Facades\Url::current()">{{ $slot }}</x-app.sidebar-link>
</div>
