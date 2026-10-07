<?php
    use Filament\Forms\Components\Toggle;
    use Filament\Forms\Components\Radio;
    use Livewire\Volt\Component;
    use function Laravel\Folio\{middleware, name};
    use Filament\Forms\Concerns\InteractsWithForms;
    use Filament\Forms\Contracts\HasForms;
    use Filament\Forms\Form;
    use Filament\Schemas\Schema;
    use Filament\Notifications\Notification;
    use App\Models\ActivityLog;
    
    middleware('auth');
    name('settings.privacy');

	new class extends Component implements HasForms
	{
        use InteractsWithForms;

        public ?array $data = [];

        public function mount(): void
        {
            $settings = auth()->user()->privacy_settings ?? $this->getDefaultSettings();
            $this->form->fill($settings);
        }

        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    Radio::make('profile_visibility')
                        ->label('Profile Visibility')
                        ->helperText('Control who can view your profile')
                        ->options([
                            'public' => 'Public - Anyone can view your profile',
                            'private' => 'Private - Only you can view your profile',
                        ])
                        ->default('public')
                        ->inline(false),
                    Toggle::make('show_email')
                        ->label('Show Email on Profile')
                        ->helperText('Display your email address on your public profile')
                        ->default(false),
                    Toggle::make('allow_search_engines')
                        ->label('Allow Search Engine Indexing')
                        ->helperText('Add noindex meta tag to prevent search engines from indexing your profile')
                        ->default(true),
                ])
                ->statePath('data');
        }
        
        public function save(): void
        {
            $state = $this->form->getState();
            $this->validate();

            $oldSettings = auth()->user()->privacy_settings ?? [];
            
            auth()->user()->forceFill([
                'privacy_settings' => $state
            ])->save();

            // Log privacy changes
            $changes = [];
            foreach ($state as $key => $value) {
                if (!isset($oldSettings[$key]) || $oldSettings[$key] !== $value) {
                    $changes[] = $key;
                }
            }
            
            if (!empty($changes)) {
                ActivityLog::log('privacy_updated', 'Privacy settings updated: ' . implode(', ', $changes), [
                    'changed_settings' => $changes
                ]);
            }

            Notification::make()
                ->title('Successfully saved privacy settings')
                ->success()
                ->send();
        }

        private function getDefaultSettings(): array
        {
            return config('privacy.defaults', [
                'profile_visibility' => 'public',
                'show_email' => false,
                'allow_search_engines' => true,
            ]);
        }

	}

?>

<x-layouts.app>
    @volt('settings.privacy') 
        <div class="">
            <x-app.settings-layout
                :title="__('Privacy Settings')"
                :description="__('Control your privacy and what information is visible to others.')"
            >
                <form wire:submit="save" class="ui:max-w-2xl">
                    <x-ui.card>
                        {{ $this->form }}
                        <div class="ui:flex ui:justify-end ui:pt-6">
                            <x-ui.button type="submit">{{ __('Save Settings') }}</x-ui.button>
                        </div>
                    </x-ui.card>
                </form>

                {{-- Privacy information --}}
                <div class="ui:flex ui:max-w-2xl ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-blue-200 ui:bg-info-soft ui:p-4">
                    <x-phosphor-info class="ui:size-5 ui:shrink-0 ui:text-info"/>
                    <div class="ui:flex ui:flex-col ui:gap-1 ui:text-sm ui:text-blue-900">
                        <span class="ui:font-semibold">{{ __('Your Privacy Matters') }}</span>
                        <p class="ui:m-0">{{ __('These settings help you control your privacy and data. Changes take effect immediately and you can update them at any time.') }}</p>
                    </div>
                </div>

            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
