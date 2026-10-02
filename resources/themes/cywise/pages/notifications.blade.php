<?php
    use function Laravel\Folio\{middleware, name};
	use Livewire\Volt\Component;
    name('notifications');
    middleware('auth');

	new class extends Component{

		public $notifications_count;
		public $unreadNotifications;
		 
		public function mount(){
			$this->updateNotifications();
		}

		public function delete($id){
			$notification = auth()->user()->notifications()->where('id', $id)->first();
			if ($notification){
				$notification->delete();
			}
			$this->updateNotifications();
		}

		public function updateNotifications(){
			$this->setUnreadNotifications = $this->unreadNotifications = auth()->user()->unreadNotifications->all();  
			$this->notifications_count = auth()->user()->unreadNotifications->count();}
		}
?>

<x-layouts.app>
	@volt('notifications')
		<div class="ui:mx-auto ui:flex ui:w-full ui:max-w-3xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

			<x-ui.page-header :title="__('Notifications')" :subtitle="__('View your current notifications')"/>

			<x-ui.card flush>
				@forelse ($unreadNotifications as $index => $notification)
					@php $notification_data = (object)$notification->data; @endphp
					<div id="notification-li-{{ $index + 1 }}" class="ui:flex ui:items-start ui:gap-4 ui:px-5 ui:py-4 @if(!$loop->first) ui:border-0 ui:border-t ui:border-solid ui:border-line @endif">
						<img class="ui:size-10 ui:shrink-0 ui:rounded-full" src="{{ @$notification_data->icon }}" alt="">

						<a href="{{ @$notification_data->link }}" class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-0.5 ui:no-underline!">
							<span class="ui:flex ui:items-baseline ui:gap-2">
								<span class="ui:text-sm ui:font-semibold ui:text-ink!">{{ @$notification_data->user['name'] }}</span>
								<time class="ui:text-xs ui:text-slate-400!">{{ \Carbon\Carbon::parse(@$notification->created_at)->translatedFormat('j F, H:i') }}</time>
							</span>
							<span class="ui:text-sm ui:text-slate-600!">{{ @$notification_data->body }} · <span class="ui:font-medium ui:text-ink!">{{ @$notification_data->title }}</span></span>
						</a>

						<x-ui.button variant="ghost" size="sm" wire:click="delete('{{ $notification->id }}')">
							<x-phosphor-check class="ui:size-4"/>
							{{ __('Mark as read') }}
						</x-ui.button>
					</div>
				@empty
					<x-ui.empty icon="bell-slash">{{ __('No notifications') }}</x-ui.empty>
				@endforelse
			</x-ui.card>
		</div>
	@endvolt

</x-layouts.app>