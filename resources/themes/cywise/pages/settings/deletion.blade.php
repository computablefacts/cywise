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
                title="Account Deletion"
                description="Permanently delete your account and all associated data.">
                
                <div class="w-100 max-w-2xl">
                    
                    @if($isScheduled)
                        <!-- Scheduled Deletion Warning -->
                        <div class="card mb-4 border-warning bg-light">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <svg class="text-warning" style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold text-dark">Account Deletion Scheduled</h5>
                                        <p class="mb-2 small">
                                            Your account is scheduled to be permanently deleted on 
                                            <strong>{{ $scheduledDate->format('F j, Y') }}</strong>
                                            ({{ abs($daysRemaining) }} {{ abs($daysRemaining) === 1 ? 'day' : 'days' }} remaining).
                                        </p>
                                        <p class="mb-3 small">
                                            After this date, all your data including your profile, posts, and settings will be permanently removed and cannot be recovered.
                                        </p>
                                        <button
                                            wire:click="cancelAccountDeletion"
                                            wire:confirm="Are you sure you want to cancel the account deletion?"
                                            class="btn btn-success btn-sm">
                                            Cancel Deletion
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Delete Account Form -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="mb-4">
                                    <h5 class="fw-bold">Delete Your Account</h5>
                                    <p class="small text-muted">
                                        Once you delete your account, there is no going back. Please be certain.
                                    </p>
                                </div>

                                <div class="alert alert-danger mb-4" role="alert">
                                    <div class="d-flex">
                                        <svg class="flex-shrink-0 me-3 mt-1" style="width: 20px; height: 20px;" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <div class="flex-grow-1">
                                            <h6 class="alert-heading fw-bold small">Warning</h6>
                                            <ul class="mb-0 small">
                                                <li>Your account will be scheduled for deletion in 30 days</li>
                                                <li>All your personal data will be permanently removed</li>
                                                <li>Your username will become available to others</li>
                                                <li>Any active subscriptions will be cancelled</li>
                                                <li>This action cannot be undone after the grace period</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <form wire:submit="scheduleAccountDeletion">
                                    <div class="mb-3">
                                        <label for="password" class="form-label small fw-bold">
                                            Confirm Your Password
                                        </label>
                                        <input 
                                            type="password" 
                                            id="password"
                                            wire:model="password"
                                            class="form-control"
                                            placeholder="Enter your password">
                                        @error('password')
                                            <p class="mt-1 small text-danger">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="form-check mb-4">
                                        <input 
                                            type="checkbox" 
                                            id="confirmDeletion"
                                            wire:model="confirmDeletion"
                                            class="form-check-input">
                                        <label for="confirmDeletion" class="form-check-label small">
                                            I understand that this action will permanently delete my account and all associated data after 30 days.
                                        </label>
                                        @error('confirmDeletion')
                                            <p class="small text-danger d-block mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="pt-2">
                                        <button 
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                            wire:loading.attr="disabled">
                                            <span wire:loading.remove>Schedule Account Deletion</span>
                                            <span wire:loading>Processing...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Additional Info -->
                        <div class="alert alert-info" role="alert">
                            <div class="d-flex">
                                <svg class="flex-shrink-0 me-3 mt-1" style="width: 20px; height: 20px;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <h6 class="alert-heading fw-bold small">Grace Period</h6>
                                    <p class="mb-0 small">
                                        You'll have 30 days to cancel the deletion if you change your mind. During this time, you can still log in and use your account normally. After 30 days, your account and all data will be permanently deleted.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>

            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
