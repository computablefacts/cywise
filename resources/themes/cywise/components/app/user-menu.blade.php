@props([
    'position' => 'bottom'
])
<div x-data="{ dropdownOpen: false }" class="position-relative flex-shrink-0 d-flex align-items-center w-100" x-cloak>
    <button @click="dropdownOpen=!dropdownOpen" class="d-flex p-2 w-100 gap-2 small hover-bg-light rounded border-0 bg-transparent justify-content-between align-items-center text-dark text-decoration-none">
        <span class="position-relative d-flex align-items-center gap-2">
            <x-avatar src="{{ auth()->user()->avatar() }}" alt="{{ auth()->user()->name }} photo" size="2xs" />
            <span @class([
                'transition-all',
                'd-none' => ($position != 'bottom')
            ])>{{ Auth::user()->name }}</span>
        </span>
        <svg :class="{ 'rotate-180' : '{{ $position }}' == 'bottom' }" class="position-relative transition-transform" style="width: 1rem; height: 1rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
    </button>
    <div wire:ignore x-show="dropdownOpen" @mouse.leave="dropdownOpen=false" @click.away="dropdownOpen=false" x-transition 
        @class([
            'z-index-1000',
            'position-absolute w-100 bottom-0 mb-5 pb-2' => ($position == 'bottom'),
            'position-fixed top-0 end-0 me-3 mt-5 w-100 shadow' => ($position != 'bottom')
        ])
        style="@if($position != 'bottom') max-width: 250px; @endif"
        x-cloak>
        <div class="bg-white border text-muted shadow-sm rounded-3">
            <div class="px-3 py-2 small fw-bold text-truncate">{{ auth()->user()->email }}</div>
            <div class="dropdown-divider my-1"></div>
            <div class="position-relative d-flex flex-column p-2 gap-1">
                <x-app.sidebar-link :hideUntilGroupHover="false" href="{{ route('notifications') }}" icon="phosphor-bell-duotone" active="false">Notifications</x-app.sidebar-link>
                <x-app.sidebar-link :hideUntilGroupHover="false" href="{{ route('settings.profile') }}" icon="phosphor-gear-duotone" active="false">Settings</x-app.sidebar-link>
                @notsubscriber
                <x-app.sidebar-link href="/settings/subscription" icon="phosphor-sparkle-duotone">Upgrade</x-app.sidebar-link>
                @endnotsubscriber
                @if(auth()->user()->isAdmin())
                <x-app.sidebar-link :hideUntilGroupHover="false" :ajax="false" href="/admin" icon="phosphor-crown-duotone" active="false">View Admin</x-app.sidebar-link>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="w-100">
                    @csrf
                    <button onclick="event.preventDefault(); this.closest('form').submit();" class="position-relative w-100 d-flex cursor-pointer hover-text-dark select-none hover-bg-light align-items-center rounded border-0 bg-transparent p-2 small transition-colors">
                        <x-phosphor-sign-out-duotone class="flex-shrink-0 me-2" style="width: 1.25rem; height: auto;" />
                        <span>Log out</span>
                    </button>
                </form>
                @impersonating
                <x-app.sidebar-link href="{{ route('impersonate.leave') }}" icon="phosphor-user-circle-duotone" active="false">Leave impersonation</x-app.sidebar-link>
                @endImpersonating
            </div>
        </div>
    </div>
</div>