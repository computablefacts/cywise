<?php
    use function Laravel\Folio\{middleware, name};
    name('subscription.welcome');
    middleware('auth');
?>

<x-layouts.app>
	<x-app.container x-data class="gap-3" x-cloak>
        <div class="w-100">
            <x-app.heading
                title="{{ __('Successfully purchased 🎉') }}"
                description="{{ __('Thanks for upgrading to a subscription plan.') }}"
            />
            <div class="py-4 gap-3">
                <p></p>
                <p></p>
            </div>
        </div>
    </x-app.container>
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