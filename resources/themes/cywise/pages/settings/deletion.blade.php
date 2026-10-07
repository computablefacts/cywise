<?php

use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

middleware('auth');
name('settings.deletion');

new class extends Component
{
    public string $password = '';
    public bool $confirmDeletion = false;

    public function with(): array
    {
        $user = auth()->user();
        return [
            'isScheduled' => !is_null($user->deletion_scheduled_at),
            'scheduledDate' => $user->deletion_scheduled_at,
            'daysRemaining' => $user->deletion_scheduled_at 
                ? (int) ceil(now()->diffInDays($user->deletion_scheduled_at, false))
                : null,
        ];
    }

    public function scheduleAccountDeletion()
    {
        $this->validate([
            'password' => 'required',
            'confirmDeletion' => 'accepted',
        ], [
            'confirmDeletion.accepted' => 'You must confirm that you understand this action.',
        ]);

        // Verify password
        if (!Hash::check($this->password, auth()->user()->password)) {
            $this->addError('password', 'The password is incorrect.');
            return;
        }

        // Schedule deletion for 30 days from now
        $user = auth()->user();
        $user->deletion_scheduled_at = now()->addDays(30);
        $user->save();

        // Log account deletion scheduling
        ActivityLog::log('account_deletion_scheduled', 'Account deletion scheduled for 30 days from now', [
            'scheduled_date' => $user->deletion_scheduled_at->toDateTimeString()
        ]);

        // Reset form
        $this->password = '';
        $this->confirmDeletion = false;

        Notification::make()
            ->title('Account deletion scheduled')
            ->body('Your account will be permanently deleted in 30 days. You can cancel this at any time before then.')
            ->warning()
            ->send();
    }

    public function cancelAccountDeletion()
    {
        $user = auth()->user();
        $user->deletion_scheduled_at = null;
        $user->save();

        // Log account deletion cancellation
        ActivityLog::log('account_deletion_cancelled', 'Account deletion was cancelled');

        Notification::make()
            ->title('Account deletion cancelled')
            ->body('Your account will not be deleted. You can continue using your account normally.')
            ->success()
            ->send();
    }
};

?>

<x-layouts.app>
    @volt('settings.deletion')
        <div class="">
            <x-app.settings-layout
                :title="__('Account Deletion')"
                :description="__('Permanently delete your account and all associated data.')">

                <div class="ui:flex ui:max-w-2xl ui:flex-col ui:gap-6">

                    @if($isScheduled)
                        {{-- Scheduled deletion: can still be cancelled --}}
                        <div class="ui:flex ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-amber-200 ui:bg-medium-soft ui:p-4">
                            <x-phosphor-warning class="ui:size-5 ui:shrink-0 ui:text-medium"/>
                            <div class="ui:flex ui:flex-col ui:gap-2 ui:text-sm ui:text-amber-900">
                                <span class="ui:font-semibold">{{ __('Account Deletion Scheduled') }}</span>
                                <p class="ui:m-0">
                                    {!! __('Your account is scheduled to be permanently deleted on <strong>:date</strong> (:remaining).', [
                                        'date' => e($scheduledDate->translatedFormat('j F Y')),
                                        'remaining' => e(trans_choice(':count day remaining|:count days remaining', abs($daysRemaining), ['count' => abs($daysRemaining)])),
                                    ]) !!}
                                </p>
                                <p class="ui:m-0">{{ __('After this date, all your data including your profile, posts, and settings will be permanently removed and cannot be recovered.') }}</p>
                                <div>
                                    <x-ui.button variant="secondary" size="sm"
                                                 wire:click="cancelAccountDeletion"
                                                 wire:confirm="{{ __('Are you sure you want to cancel the account deletion?') }}">
                                        {{ __('Cancel Deletion') }}
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Delete account form --}}
                        <x-ui.card :title="__('Delete Your Account')" :subtitle="__('Once you delete your account, there is no going back. Please be certain.')">
                            <div class="ui:flex ui:flex-col ui:gap-5">
                                <div class="ui:flex ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-red-200 ui:bg-high-soft ui:p-4">
                                    <x-phosphor-warning class="ui:size-5 ui:shrink-0 ui:text-high"/>
                                    <div class="ui:flex ui:flex-col ui:gap-1 ui:text-sm ui:text-red-900">
                                        <span class="ui:font-semibold">{{ __('Warning') }}</span>
                                        <ul class="ui:m-0 ui:flex ui:flex-col ui:gap-0.5 ui:pl-5">
                                            <li>{{ __('Your account will be scheduled for deletion in 30 days') }}</li>
                                            <li>{{ __('All your personal data will be permanently removed') }}</li>
                                            <li>{{ __('Your username will become available to others') }}</li>
                                            <li>{{ __('Any active subscriptions will be cancelled') }}</li>
                                            <li>{{ __('This action cannot be undone after the grace period') }}</li>
                                        </ul>
                                    </div>
                                </div>

                                <form wire:submit="scheduleAccountDeletion" class="ui:flex ui:flex-col ui:gap-4">
                                    <x-ui.field :label="__('Confirm Your Password')" for="password">
                                        <x-ui.input type="password" id="password" wire:model="password" :placeholder="__('Enter your password')"/>
                                        @error('password')
                                            <p class="ui:m-0 ui:text-xs ui:text-critical">{{ $message }}</p>
                                        @enderror
                                    </x-ui.field>

                                    <div class="ui:flex ui:flex-col ui:gap-1">
                                        <label for="confirmDeletion" class="ui:m-0 ui:flex ui:cursor-pointer ui:items-start ui:gap-2 ui:text-sm ui:text-slate-600">
                                            <input type="checkbox" id="confirmDeletion" wire:model="confirmDeletion"
                                                   class="ui:mt-0.5 ui:size-4 ui:shrink-0 ui:cursor-pointer ui:accent-brand-500">
                                            <span>{{ __('I understand that this action will permanently delete my account and all associated data after 30 days.') }}</span>
                                        </label>
                                        @error('confirmDeletion')
                                            <p class="ui:m-0 ui:text-xs ui:text-critical">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Danger action: x-ui.button has no destructive variant --}}
                                    <div>
                                        <button type="submit" wire:loading.attr="disabled"
                                                class="ui:inline-flex ui:h-10 ui:cursor-pointer ui:items-center ui:justify-center ui:gap-2 ui:rounded-lg ui:border-0 ui:bg-critical ui:px-4 ui:text-sm ui:font-medium ui:whitespace-nowrap ui:text-white ui:shadow-xs ui:transition-colors ui:hover:bg-red-700 ui:disabled:cursor-not-allowed ui:disabled:opacity-50">
                                            <x-phosphor-trash class="ui:size-4"/>
                                            <span wire:loading.remove>{{ __('Schedule Account Deletion') }}</span>
                                            <span wire:loading>{{ __('Processing...') }}</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </x-ui.card>

                        {{-- Grace period --}}
                        <div class="ui:flex ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-blue-200 ui:bg-info-soft ui:p-4">
                            <x-phosphor-info class="ui:size-5 ui:shrink-0 ui:text-info"/>
                            <div class="ui:flex ui:flex-col ui:gap-1 ui:text-sm ui:text-blue-900">
                                <span class="ui:font-semibold">{{ __('Grace Period') }}</span>
                                <p class="ui:m-0">{{ __("You'll have 30 days to cancel the deletion if you change your mind. During this time, you can still log in and use your account normally. After 30 days, your account and all data will be permanently deleted.") }}</p>
                            </div>
                        </div>
                    @endif
                </div>

            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
