<x-card class="d-flex flex-column w-100 mx-auto my-md-4" style="max-width: 900px;">
    <div class="d-flex flex-wrap align-items-center justify-content-between pb-3 border-bottom">
        <div class="position-relative p-2">
            <div>
                <h2 class="h5 fw-bold mb-1">{{ $title ?? '' }}</h2>
                <p class="small text-muted mb-0">{{ $description ?? '' }}</p>
            </div>
        </div>
    </div>
    <div class="d-flex flex-column pt-4 d-lg-row">
        <aside class="flex-shrink-0 pb-4 lg-pb-0" style="width: 200px;">
            <nav class="d-flex align-items-start justify-content-start flex-lg-column gap-1">
                <div class="px-2 pb-1 small d-none d-lg-block fw-bold text-muted">Settings</div>
                <div class="d-flex align-items-center w-auto gap-1 flex-lg-column w-lg-100">
                    <x-settings-sidebar-link :href="route('settings.profile')" icon="phosphor-user-circle-duotone">Profile</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.security')" icon="phosphor-lock-duotone">Security</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.notifications')" icon="phosphor-bell-duotone">Notifications</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.social')" icon="phosphor-share-network-duotone">Social Media</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.api')" icon="phosphor-code-duotone">API Keys</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.activity')" icon="phosphor-clock-counter-clockwise-duotone">Activity Log</x-settings-sidebar-link>
                </div>
                <div class="px-2 pt-3 pb-1 small d-none d-lg-block fw-bold text-muted">Billing</div>
                <div class="d-flex align-items-center w-100 gap-1 flex-lg-column">
                    <x-settings-sidebar-link :href="route('settings.subscription')" icon="phosphor-credit-card-duotone">Subscription</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.invoices')" icon="phosphor-invoice-duotone">Invoices</x-settings-sidebar-link>
                </div>
                <div class="px-2 pt-3 pb-1 small d-none d-lg-block fw-bold text-muted">Privacy</div>
                <div class="d-flex align-items-center w-100 gap-1 flex-lg-column">
                    <x-settings-sidebar-link :href="route('settings.privacy')" icon="phosphor-shield-check-duotone">Privacy Settings</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.export')" icon="phosphor-download-duotone">Export Data</x-settings-sidebar-link>
                    <x-settings-sidebar-link :href="route('settings.deletion')" icon="phosphor-trash-duotone">Account Deletion</x-settings-sidebar-link>
                </div>
            </nav>
        </aside>

        <div class="py-3 px-lg-4 w-100">
            {{ $slot }}
        </div>
    </div>
</x-card>
