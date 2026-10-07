<?php

    use function Laravel\Folio\{middleware, name};
    use Filament\Forms\Concerns\InteractsWithForms;
    use Filament\Forms\Contracts\HasForms;
    use Filament\Forms\Form;
    use Filament\Schemas\Schema;
    use Filament\Notifications\Notification;
	use Livewire\Volt\Component;
	use Wave\Traits\HasDynamicFields;
    use Wave\ApiKey;
    use App\Models\ActivityLog;

	middleware('auth');
    name('settings.profile');

	new class extends Component implements HasForms
	{
        use InteractsWithForms, HasDynamicFields;

        public ?array $data = [];
		public ?string $avatar = null;

		public function mount(): void
        {
            $this->form->fill();
        }

       public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
						->rules('required|string')
						->default(auth()->user()->name),
					\Filament\Forms\Components\TextInput::make('username')
                        ->label('Username')
                        ->required()
						->rules('sometimes|required|string|alpha_dash|max:255|unique:users,username,' . auth()->user()->id)
						->helperText('Your unique username used in your profile URL')
						->default(auth()->user()->username),
					\Filament\Forms\Components\TextInput::make('email')
                        ->label('Email Address')
                        ->required()
						->rules('sometimes|required|email|unique:users,email,' . auth()->user()->id)
						->default(auth()->user()->email),
					...($this->dynamicFields( config('profile.fields') ))
                ])
                ->statePath('data');
        }

		public function save()
		{
			$this->validate([
				'avatar' => 'sometimes|nullable|imageable',
			]);

			$state = $this->form->getState();
            $this->validate();

			if($this->avatar != null){
				$this->saveNewUserAvatar();
			}

			$this->saveFormFields($state);

			Notification::make()
                ->title('Successfully saved your profile settings')
                ->success()
                ->send();
		}

	private function saveNewUserAvatar(){
		$path = 'avatars/' . auth()->user()->username . '.png';
		$image = app('image')->read($this->avatar)->resize(800, 800);
		Storage::disk('public')->put($path, $image->encode());
		auth()->user()->avatar = $path;
		auth()->user()->save();
		
		// Log avatar update
		ActivityLog::log('avatar_updated', 'Profile avatar was updated');
		
		// This will update/refresh the avatar in the sidebar
		$this->js('window.dispatchEvent(new CustomEvent("refresh-avatar"));');
	}	private function saveFormFields($state){
		// Track changes for activity log
		$user = auth()->user();
		$changes = [];
		
		if($user->name !== $state['name']) {
			$changes[] = 'name';
		}
		if($user->username !== $state['username']) {
			$changes[] = 'username';
		}
		if($user->email !== $state['email']) {
			$changes[] = 'email';
		}
		
		$user->name = $state['name'];
		$user->username = $state['username'];
		$user->email = $state['email'];
		$user->save();
		$fieldsToSave = config('profile.fields');
		$this->saveDynamicFields($fieldsToSave);
		
		// Log the profile update
		if(!empty($changes)) {
			ActivityLog::log('profile_updated', 'Profile updated: ' . implode(', ', $changes), [
				'changed_fields' => $changes
			]);
		}
		}

	}
?>

<x-layouts.app>

    <x-app.settings-layout
        :title="__('Settings')"
        :description="__('Manage your account avatar, name, email, and more.')">

		@volt('settings.profile')
		<div class="ui:w-full" x-data="{
				uploadCropEl: null,
				uploadLoading: null,
				fileTypes: null,
				avatar: @entangle('avatar'),
				readFile() {
					input = document.getElementById('upload');
					if (input.files && input.files[0]) {
						let reader = new FileReader();

						let fileType = input.files[0].name.split('.').pop().toLowerCase();
						if (this.fileTypes.indexOf(fileType) < 0) {
							alert('Invalid file type. Please select a JPG or PNG file.');
							return false;
						}
						reader.onload = function (e) {
							uploadCrop.bind({
								url: e.target.result,
								orientation: 4
							}).then(function(){
								//uploadCrop.setZoom(0);
							});
						}
						reader.readAsDataURL(input.files[0]);
					}
					else {
						alert('Sorry - you\'re browser doesn\'t support the FileReader API');
					}
				},
				applyImageCrop(){
					let fileType = input.files[0].name.split('.').pop().toLowerCase();
					if (this.fileTypes.indexOf(fileType) < 0) {
						alert('Invalid file type. Please select a JPG or PNG file.');
						return false;
					}
					let that = this;
					uploadCrop.result({type:'base64',size:'original',format:'png',quality:1}).then(function(base64) {
						that.avatar = base64;
						document.getElementById('preview').src = that.avatar;
					});

				}
			}"
		x-init="
			uploadCropEl = document.getElementById('upload-crop');
			uploadLoading = document.getElementById('uploadLoading');
			fileTypes = ['jpg', 'jpeg', 'png'];

			if(document.getElementById('upload')){
				document.getElementById('upload').addEventListener('change', function () {
					window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'profile-avatar-crop' }}));
					uploadCropEl.classList.add('hidden');
					uploadLoading.classList.remove('hidden');
					setTimeout(function(){
						uploadLoading.classList.add('hidden');
						uploadCropEl.classList.remove('hidden');

						if(typeof(uploadCrop) != 'undefined'){
							uploadCrop.destroy();
						}
						uploadCrop = new Croppie(uploadCropEl, {
							viewport: { width: 190, height: 190, type: 'square' },
							boundary: { width: 190, height: 190 },
							enableExif: true,
						});

						readFile();
					}, 800);
				});
			}
		">
			<form wire:submit="save">
				<x-ui.card>
					<div class="ui:flex ui:flex-col ui:gap-6">

						{{-- Avatar: the transparent file input covers the picture; a change opens the crop modal --}}
						<div class="ui:flex ui:flex-col ui:gap-2">
							<div class="ui:relative ui:size-32 ui:shrink-0 ui:cursor-pointer">
								<img id="preview" src="{{ auth()->user()->avatar() . '?' . time() }}" class="ui:size-32 ui:rounded-full ui:object-cover ui:ring-1 ui:ring-line" alt="">
								<input type="file" id="upload" class="ui:absolute ui:inset-0 ui:z-20 ui:size-full ui:cursor-pointer ui:opacity-0" aria-label="{{ __('Change avatar') }}">
								<span class="ui:absolute ui:bottom-2 ui:left-1/2 ui:z-10 ui:flex ui:size-10 ui:-translate-x-1/2 ui:items-center ui:justify-center ui:rounded-full ui:bg-ink/75 ui:text-white">
									<x-phosphor-camera class="ui:size-5"/>
								</span>
							</div>
							@error('avatar')
								<p class="ui:m-0 ui:text-sm ui:text-critical">{{ __('The avatar must be a valid image type.') }}</p>
							@enderror
						</div>

						{{ $this->form }}

						<div class="ui:flex ui:justify-end">
							<x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
						</div>
					</div>
				</x-ui.card>
			</form>

			<div style="z-index: 1050;">
				<x-filament::modal id="profile-avatar-crop">
					<div class="ui:flex ui:flex-col ui:items-center ui:gap-3">
						<h2 class="ui:m-0 ui:text-base ui:font-semibold ui:text-ink" id="modal-headline">
							{{ __('Position and resize your photo') }}
						</h2>
						<div id="upload-crop-container" class="ui:relative ui:flex ui:h-56 ui:w-full ui:items-center ui:justify-center">
							<div id="uploadLoading" class="ui:flex ui:size-full ui:items-center ui:justify-center">
								<x-phosphor-circle-notch class="ui:size-8 ui:animate-spin ui:text-brand-500" role="status" aria-label="{{ __('Loading...') }}"/>
							</div>
							<div id="upload-crop"></div>
						</div>
					</div>
					<div class="ui:mt-4 ui:flex ui:justify-end ui:gap-2">
						<x-ui.button variant="secondary" @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'profile-avatar-crop' }}));">{{ __('Cancel') }}</x-ui.button>
						<x-ui.button id="apply-crop" @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'profile-avatar-crop' }})); applyImageCrop()">{{ __('Apply') }}</x-ui.button>
					</div>
				</x-filament::modal>
			</div>
		</div>
		@endvolt
    </x-app.settings-layout>

	<x-slot:javascript>
		<style>
			#upload-crop-container .croppie-container .cr-resizer, #upload-crop-container .croppie-container .cr-viewport{
				box-shadow: 0 0 2000px 2000px rgba(255,255,255,1) !important;
				border: 0px !important;
			}
			.croppie-container .cr-boundary {
				border-radius: 50% !important;
				overflow: hidden;
			}
			.croppie-container .cr-slider-wrap{
				margin-bottom: 0px !important;
			}
			.croppie-container{
				height:auto !important;
			}
		</style>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/exif-js/2.3.0/exif.min.js"></script>
		<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.2/croppie.min.css">
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.2/croppie.min.js"></script>
	</x-slot>

</x-layouts.app>
