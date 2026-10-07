{{--
  Shell of every settings page: page header, then nav + content.

    Title
    subtitle
    ┌──────────────┬──────────────────────┐
    │ SETTINGS     │                      │
    │  Profile     │  {{ $slot }}         │
    │  …           │                      │
    │ BILLING …    │                      │
    └──────────────┴──────────────────────┘

  Below lg, the nav becomes a horizontal scrolling row above the content.
--}}
@props([
    'title' => '',
    'description' => null,
])

<div class="ui:mx-auto ui:flex ui:w-full ui:max-w-6xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="$title" :subtitle="$description"/>

    <div class="ui:flex ui:flex-col ui:gap-6 ui:lg:flex-row ui:lg:items-start">

        {{-- Nav: same links as the app sidebar --}}
        <aside class="ui:min-w-0 ui:shrink-0 ui:lg:w-56">
            <nav class="ui:flex ui:gap-1 ui:overflow-x-auto ui:lg:flex-col ui:lg:gap-4 ui:lg:overflow-visible">
                <div class="ui:flex ui:gap-1 ui:lg:flex-col ui:lg:gap-0.5">
                    <span class="ui:hidden ui:px-3 ui:py-1 ui:text-[11px] ui:font-semibold ui:uppercase ui:tracking-wider ui:text-slate-400 ui:lg:block">{{ __('Settings') }}</span>
                    <x-settings-sidebar-link :href="route('settings.profile')" icon="phosphor-user-circle">{{ __('Profile') }}</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.security')" icon="phosphor-lock">{{ __('Security') }}</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.notifications')" icon="phosphor-bell">{{ __('Notifications') }}</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.social')" icon="phosphor-share-network">{{ __('Social Media') }}</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.api')" icon="phosphor-code">{{ __('API Keys') }}</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.activity')" icon="phosphor-clock-counter-clockwise">{{ __('Activity Log') }}</x-settings-sidebar-link>
                </div>
                <div class="ui:flex ui:gap-1 ui:lg:flex-col ui:lg:gap-0.5">
                    <span class="ui:hidden ui:px-3 ui:py-1 ui:text-[11px] ui:font-semibold ui:uppercase ui:tracking-wider ui:text-slate-400 ui:lg:block">{{ __('Billing') }}</span>
                    <x-settings-sidebar-link :href="route('settings.subscription')" icon="phosphor-credit-card">{{ __('Subscription') }}</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.invoices')" icon="phosphor-invoice">{{ __('Invoices') }}</x-settings-sidebar-link>
                </div>
                <div class="ui:flex ui:gap-1 ui:lg:flex-col ui:lg:gap-0.5">
                    <span class="ui:hidden ui:px-3 ui:py-1 ui:text-[11px] ui:font-semibold ui:uppercase ui:tracking-wider ui:text-slate-400 ui:lg:block">{{ __('Privacy') }}</span>
                    <x-settings-sidebar-link :href="route('settings.privacy')" icon="phosphor-shield-check">{{ __('Privacy Settings') }}</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.export')" icon="phosphor-download-simple">{{ __('Export Data') }}</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.deletion')" icon="phosphor-trash">{{ __('Account Deletion') }}</x-settings-sidebar-link>
                </div>
            </nav>
        </aside>

        <div class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-6">
            {{ $slot }}
        </div>
    </div>
</div>
