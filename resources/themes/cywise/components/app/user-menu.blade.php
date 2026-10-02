@php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($user->name)))->take(2)->map(fn($w) => mb_substr($w, 0, 1))->join('');
@endphp

<div x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen=false" @keydown.escape="dropdownOpen=false" class="ui:relative">
    <button type="button" @click="dropdownOpen=!dropdownOpen" :aria-expanded="dropdownOpen"
            class="ui:flex ui:items-center ui:gap-3 ui:rounded-lg ui:border-0 ui:bg-transparent ui:py-1.5 ui:pl-1.5 ui:pr-2 ui:hover:bg-slate-100 ui:cursor-pointer">
        <span class="ui:flex ui:size-8 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-lg ui:bg-ink ui:text-xs ui:font-semibold ui:uppercase ui:text-white">
            {{ $initials }}
        </span>
        <span class="ui:hidden ui:max-w-44 ui:truncate ui:text-sm ui:font-medium ui:text-ink ui:md:block">{{ $user->name }}</span>
        <x-phosphor-caret-down class="ui:size-4 ui:text-slate-400"/>
    </button>

    <div x-show="dropdownOpen" x-transition.origin.top.right x-cloak
         class="ui:absolute ui:right-0 ui:z-50 ui:mt-2 ui:w-64 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-1.5 ui:shadow-lg">
        <div class="ui:truncate ui:px-3 ui:py-2 ui:text-xs ui:font-medium ui:text-slate-500">{{ $user->email }}</div>
        <div class="ui:my-1 ui:h-px ui:bg-line"></div>
        <x-app.sidebar-link href="{{ route('settings.profile') }}" icon="phosphor-gear">{{ __('Settings') }}</x-app.sidebar-link>
        @notsubscriber
        <x-app.sidebar-link href="/settings/subscription" icon="phosphor-sparkle">{{ __('Upgrade') }}</x-app.sidebar-link>
        @endnotsubscriber
        @if($user->isAdmin())
        <x-app.sidebar-link :ajax="false" href="/admin" icon="phosphor-crown">{{ __('View Admin') }}</x-app.sidebar-link>
        @endif
        @impersonating
        <x-app.sidebar-link href="{{ route('impersonate.leave') }}" icon="phosphor-user-circle">{{ __('Leave impersonation') }}</x-app.sidebar-link>
        @endImpersonating
        <div class="ui:my-1 ui:h-px ui:bg-line"></div>
        <form method="POST" action="{{ route('logout') }}" class="ui:m-0">
            @csrf
            <button type="submit"
                    class="ui:flex ui:w-full ui:items-center ui:gap-3 ui:rounded-lg ui:border-0 ui:bg-transparent ui:px-3 ui:py-2 ui:text-sm ui:text-slate-600 ui:hover:bg-slate-100 ui:hover:text-ink ui:cursor-pointer">
                <x-phosphor-sign-out class="ui:size-5 ui:shrink-0"/>
                <span>{{ __('Log out') }}</span>
            </button>
        </form>
    </div>
</div>
