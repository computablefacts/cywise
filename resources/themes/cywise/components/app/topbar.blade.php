@php
    $hasUnread = auth()->user()->unreadNotifications()->exists();
@endphp

<header class="ui:sticky ui:top-0 ui:z-30 ui:flex ui:h-16 ui:items-center ui:gap-3 ui:border-0 ui:border-b ui:border-solid ui:border-line ui:bg-white/90 ui:px-4 ui:backdrop-blur ui:lg:px-8">

    {{-- Mobile: open sidebar --}}
    <button type="button" @click="window.dispatchEvent(new CustomEvent('open-sidebar'))"
            class="ui:flex ui:size-10 ui:items-center ui:justify-center ui:rounded-lg ui:border-0 ui:bg-transparent ui:text-slate-600 ui:hover:bg-slate-100 ui:lg:hidden"
            aria-label="{{ __('Menu') }}">
        <x-phosphor-list class="ui:size-6"/>
    </button>

    {{-- Quick action: start monitoring a domain or an IP, from any page --}}
    <form x-data="{ asset: '', busy: false }"
          @submit.prevent="
            if (!asset.trim()) { return; }
            busy = true;
            createAssetApiCall(asset.trim(), true, () => {
              window.toaster.toastSuccess(@js(__('The monitoring started.')));
              window.dispatchEvent(new CustomEvent('asset-created'));
              asset = '';
            });
            setTimeout(() => busy = false, 1000);
          "
          class="ui:m-0 ui:hidden ui:max-w-lg ui:flex-1 ui:items-center ui:gap-2 ui:sm:flex">
        <label class="ui:relative ui:m-0 ui:flex-1">
            <span class="ui:sr-only">{{ __('Domain or IP') }}</span>
            <x-phosphor-globe class="ui:pointer-events-none ui:absolute ui:left-3 ui:top-1/2 ui:size-4 ui:-translate-y-1/2 ui:text-slate-400"/>
            <input type="text" x-model="asset"
                   placeholder="{{ __('Monitor a domain or an IP address…') }}"
                   class="ui:h-10 ui:w-full ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-slate-50 ui:pl-9 ui:pr-3 ui:text-sm ui:text-ink ui:placeholder:text-slate-400 ui:focus:border-brand-500 ui:focus:bg-white ui:focus:outline-none ui:focus:ring-2 ui:focus:ring-brand-100">
        </label>
        <x-ui.button type="submit" variant="secondary" x-bind:disabled="busy">{{ __('Monitor') }}</x-ui.button>
    </form>

    {{-- Quick action, next to "Monitor": agent install command (Linux / Windows), in a popover --}}
    <div x-data="{ open: false }" @keydown.escape.window="open = false" class="ui:relative">
        <button type="button" @click="open = !open" :aria-expanded="open"
                class="ui:flex ui:h-10 ui:items-center ui:gap-2 ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:px-3 ui:text-sm ui:font-medium ui:text-slate-600 ui:hover:text-ink ui:hover:bg-slate-100 ui:cursor-pointer"
                title="{{ __('Install an agent') }}">
            <x-phosphor-terminal-window class="ui:size-5"/>
            <span class="ui:hidden ui:lg:inline">{{ __('Install an agent') }}</span>
        </button>
        <div x-show="open" x-transition.opacity @click.outside="open = false" x-cloak
             class="ui:fixed ui:inset-x-4 ui:top-16 ui:z-40 ui:sm:absolute ui:sm:inset-x-auto ui:sm:left-0 ui:sm:top-12 ui:w-[min(36rem,calc(100vw-2rem))] ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-4 ui:shadow-lg">
            @include('theme::iframes.dashboard._server-install')
        </div>
    </div>

    <div class="ui:ml-auto ui:flex ui:items-center ui:gap-2">

        {{-- Quick access: CyberBuddy, highlighted (it is not in the sidebar) --}}
        @if(auth()->user()->canView('iframes.cyberbuddy'))
            <a href="{{ route('cyberbuddy') }}"
               class="ui:flex ui:h-10 ui:items-center ui:gap-2 ui:rounded-lg ui:bg-brand-500 ui:px-3 ui:text-sm ui:font-semibold ui:text-white! ui:shadow-xs ui:hover:bg-brand-600 ui:no-underline! {{ Request::is('cyberbuddy') || Request::is('conversations') ? 'ui:ring-2 ui:ring-brand-200' : '' }}"
               title="{{ tenant_custom_text('CyberBuddy') }}">
                <x-phosphor-robot class="ui:size-5"/>
                <span class="ui:hidden ui:sm:inline">{{ tenant_custom_text('CyberBuddy') }}</span>
            </a>
        @endif

        <a href="{{ route('notifications') }}"
           class="ui:relative ui:flex ui:size-10 ui:items-center ui:justify-center ui:rounded-lg ui:text-slate-600! ui:hover:text-ink! ui:hover:bg-slate-100 ui:no-underline!"
           aria-label="{{ __('Notifications') }}">
            <x-phosphor-bell class="ui:size-5"/>
            @if($hasUnread)
                <span class="ui:absolute ui:right-2.5 ui:top-2.5 ui:size-2 ui:rounded-full ui:bg-brand-500 ui:ring-2 ui:ring-white"></span>
            @endif
        </a>
        <div class="ui:h-6 ui:w-px ui:bg-line"></div>
        <x-app.user-menu/>
    </div>
</header>
