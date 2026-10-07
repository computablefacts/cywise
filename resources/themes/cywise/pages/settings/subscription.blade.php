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
                :title="__('Subscriptions')"
                :description="__('Your subscription details')"
            >
                @role('admin')
                    <div id="no_subscriptions" class="ui:flex ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-blue-200 ui:bg-info-soft ui:p-4">
                        <x-phosphor-info class="ui:size-5 ui:shrink-0 ui:text-info"/>
                        <p class="ui:m-0 ui:text-sm ui:text-blue-900">{{ __('You are logged in as an admin and have full access. Authenticate with a different user and visit this page to see the subscription checkout process.') }}</p>
                    </div>
                @else
                    @subscriber
                        <div id="no_subscriptions" class="ui:flex ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-emerald-200 ui:bg-low-soft ui:p-4">
                            <x-phosphor-seal-check class="ui:size-5 ui:shrink-0 ui:text-low"/>
                            <p class="ui:m-0 ui:text-sm ui:text-emerald-900">{{ __('You are currently subscribed to the :plan :interval Plan.', ['plan' => auth()->user()->plan()->name, 'interval' => auth()->user()->planInterval()]) }}</p>
                        </div>

                        <x-ui.card :title="__('Manage your subscription by clicking below.')">
                            @if (session('update'))
                                <p class="ui:m-0 ui:mb-4 ui:text-sm ui:text-low">{{ __('Successfully updated your subscription') }}</p>
                            @endif
                            <livewire:billing.update />
                        </x-ui.card>
                    @endsubscriber

                    @notsubscriber
                        <div id="no_subscriptions" class="ui:flex ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-blue-200 ui:bg-info-soft ui:p-4">
                            <x-phosphor-shopping-bag-open class="ui:size-5 ui:shrink-0 ui:text-info"/>
                            <p class="ui:m-0 ui:text-sm ui:text-blue-900">{{ __('No active subscriptions found. Please select a plan below.') }}</p>
                        </div>

                        <livewire:billing.checkout />

                        <p class="ui:m-0 ui:flex ui:items-center ui:gap-1.5 ui:text-sm ui:text-slate-500">
                            <x-phosphor-shield-check class="ui:size-4 ui:shrink-0"/>
                            <span>{!! __('Billing is securely managed via <strong>:provider Payment Platform</strong>.', ['provider' => e(ucfirst(config('wave.billing_provider')))]) !!}</span>
                        </p>
                    @endnotsubscriber
                @endrole
            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
