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
        title="Settings"
        description="Manage your account avatar, name, email, and more.">

		@volt('settings.profile')
		<div x-data="{
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
		"
		<div class="w-100">
			<form wire:submit="save" class="w-100">
				<div class="d-flex flex-column mt-3">
					<div class="position-relative flex-shrink-0" style="width: 128px; height: 128px; cursor: pointer;">
						<img id="preview" src="{{ auth()->user()->avatar() . '?' . time() }}" class="rounded-circle" style="width: 128px; height: 128px;">

						<div class="position-absolute top-0 start-0 w-100 h-100">
							<input type="file" id="upload" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" style="z-index: 20;">
							<button type="button" class="position-absolute bottom-0 start-50 translate-middle-x mb-2 btn btn-dark btn-sm rounded-circle opacity-75" style="z-index: 10; width: 40px; height: 40px;">
								<svg class="text-light" style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
							</button>
						</div>
					</div>
					@error('avatar')
						<p class="mt-2 small text-danger">The avatar must be a valid image type.</p>
					@enderror
					<div class="w-100 mt-4">
						{{ $this->form }}
					</div>
					<div class="w-100 pt-4 text-end">
						<x-button type="submit">Save</x-button>
					</div>
				</div>

			</form>

			<div style="z-index: 1050;">
				<x-filament::modal id="profile-avatar-crop">
					<div>
						<div class="mt-3 text-center">
							<h5 class="fw-bold mb-3" id="modal-headline">
								Position and resize your photo
							</h5>
							<div class="mt-2">
								<div id="upload-crop-container" class="position-relative d-flex align-items-center justify-content-center mt-3" style="height: 224px;">
									<div id="uploadLoading" class="d-flex align-items-center justify-content-center w-100 h-100">
										<div class="spinner-border text-primary" role="status">
											<span class="visually-hidden">Loading...</span>
										</div>
									</div>
									<div id="upload-crop"></div>
								</div>
							</div>
						</div>
					</div>
					<div class="mt-4 d-flex justify-content-end">
						<button @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'profile-avatar-crop' }}));" class="btn btn-outline-secondary me-2" type="button">Cancel</button>
						<button @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'profile-avatar-crop' }})); applyImageCrop()" class="btn btn-primary" id="apply-crop" type="button">Apply</button>
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
