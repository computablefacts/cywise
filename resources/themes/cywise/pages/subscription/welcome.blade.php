<?php
    use function Laravel\Folio\{middleware, name};
    name('subscription.welcome');
    middleware('auth');
?>

<x-layouts.app>
	<div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">
        <x-ui.page-header :title="__('Successfully purchased 🎉')" :subtitle="__('Thanks for upgrading to a subscription plan.')">
            <x-slot:actions>
                <x-ui.button icon="arrow-right" :href="route('dashboard')">{{ __('Dashboard') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </div>
    <x-slot name="javascript">
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
        <script>
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        </script>
    </x-slot>
</x-layouts.app>