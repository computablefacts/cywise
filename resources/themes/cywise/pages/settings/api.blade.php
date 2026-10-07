<?php
    use Filament\Forms\Components\TextInput;
    use Livewire\Volt\Component;
    use function Laravel\Folio\{middleware, name};
    use Filament\Forms\Concerns\InteractsWithForms;
    use Filament\Forms\Contracts\HasForms;
    use Filament\Actions\Concerns\InteractsWithActions;
    use Filament\Actions\Contracts\HasActions;
    use Filament\Forms\Form;
    use Filament\Schemas\Schema;
    use Filament\Notifications\Notification;
    use Filament\Tables;
    use Filament\Tables\Table;
    use Filament\Tables\Actions\Action;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Actions\DeleteAction;
    use Filament\Actions\EditAction;
    use Filament\Actions\ViewAction;

    use Illuminate\Support\Str;
    use Wave\ApiKey;
    use App\Models\ActivityLog;
    
    middleware('auth');
    name('settings.api');

	new class extends Component implements HasForms, HasActions, Tables\Contracts\HasTable
	{
        use InteractsWithForms, InteractsWithActions, Tables\Concerns\InteractsWithTable;
        
        // variables for (b)rowing keys
        public $keys = [];
        
        public ?array $data = [];

        public function mount(): void
        {
            $this->form->fill();
            $this->refreshKeys();
        }

        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    TextInput::make('key')
                        ->label('Create a new API Key')
                        ->required()
                ])
                ->statePath('data');
        }

        public function add(){

            $state = $this->form->getState();
            $this->validate();

            $apiKey = auth()->user()->createApiKey(Str::slug($state['key']));

            // Log API key creation
            ActivityLog::log('api_key_created', 'API key created: ' . $state['key'], [
                'key_name' => $state['key']
            ]);

            Notification::make()
                ->title('Successfully created new API Key')
                ->success()
                ->send();

            $this->form->fill();

            $this->refreshKeys();
        }

        public function table(Table $table): Table
        {
            return $table->query(Wave\ApiKey::query()->where('user_id', auth()->user()->id))
                ->columns([
                    TextColumn::make('name'),
                    TextColumn::make('created_at')->label('Created'),
                ])
                ->actions([
                    ViewAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->form([
                            TextInput::make('name'),
                            TextInput::make('key')
                            // ...
                        ]),
                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->form([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            // ...
                        ])
                        ->after(function($record) {
                            ActivityLog::log('api_key_updated', 'API key updated: ' . $record->name, [
                                'key_name' => $record->name
                            ]);
                        }),
                    DeleteAction::make()
                        ->after(function($record) {
                            ActivityLog::log('api_key_deleted', 'API key deleted: ' . $record->name, [
                                'key_name' => $record->name
                            ]);
                        }),
            ]);
        }

        public function refreshKeys(){
            $this->keys = auth()->user()->apiKeys;
        }


	}

?>

<x-layouts.app>
    @volt('settings.api') 
        <div class="">
            <x-app.settings-layout
                :title="__('API Keys')"
                :description="__('Manage your API Keys')"
            >
                <form wire:submit="add" class="ui:max-w-2xl">
                    <x-ui.card>
                        {{ $this->form }}
                        <div class="ui:flex ui:justify-end ui:pt-6">
                            <x-ui.button type="submit" icon="plus">{{ __('Create New Key') }}</x-ui.button>
                        </div>
                    </x-ui.card>
                </form>

                {{-- Filament table: keeps its own styling --}}
                <x-ui.card :title="__('Current API Keys')">
                    {{ $this->table }}
                </x-ui.card>
            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
