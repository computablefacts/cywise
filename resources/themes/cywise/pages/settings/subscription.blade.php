<?php
    
    use Filament\Forms\Components\TextInput;
    use Livewire\Volt\Component;
    use function Laravel\Folio\{middleware, name};
    use Filament\Forms\Concerns\InteractsWithForms;
    use Filament\Forms\Contracts\HasForms;
    use Filament\Forms\Form;
    use Filament\Notifications\Notification;
    
    middleware('auth');
    name('settings.subscription');

	new class extends Component
	{
        public function mount(): void
        {
            
        }
    }

?>

<x-layouts.app>
    @volt('settings.subscription') 
        <div class="">
            <x-app.settings-layout
                title="Subscriptions"
                description="Your subscription details"
            >
                @role('admin')
                    <x-app.alert id="no_subscriptions" :dismissable="false" type="info">
                        You are logged in as an admin and have full access. Authenticate with a different user and visit this page to see the subscription checkout process.
                    </x-app.alert>
                @else
                    @subscriber
                        
                        <div class="w-100">                            
                            <x-app.alert id="no_subscriptions" :dismissable="false" type="success">
                                <div class="d-flex align-items-center w-100">
                                    <x-phosphor-seal-check-duotone class="flex-shrink-0 me-2" style="width: 24px; height: 24px;" /> 
                                    <span>You are currently subscribed to the {{ auth()->user()->plan()->name }} {{ auth()->user()->planInterval() }} Plan.</span>
                                </div>
                            </x-app.alert>
                            <p class="my-4">Manage your subscription by clicking below.</p>
                            @if (session('update'))
                                <div class="my-4 small text-success">Successfully updated your subscription</div>
                            @endif
                            <livewire:billing.update />
                        </div>
                    @endsubscriber

                    @notsubscriber
                        <div class="mb-4">
                            <x-app.alert id="no_subscriptions" :dismissable="false" type="info">
                                <div class="d-flex align-items-center">
                                    <x-phosphor-shopping-bag-open-duotone class="flex-shrink-0 me-2" style="width: 24px; height: 24px;" />
                                    <span>No active subscriptions found. Please select a plan below.</span>
                                </div>
                            </x-app.alert>
                        </div>
                        <livewire:billing.checkout />
                        <p class="d-flex align-items-center mt-3 mb-4">
                            <x-phosphor-shield-check-duotone class="me-1" style="width: 16px; height: 16px;" />
                            <span class="me-1">Billing is securely managed via </span><strong>{{ ucfirst(config('wave.billing_provider')) }} Payment Platform</strong>.
                        </p>
                    @endnotsubscriber
                @endrole
            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
