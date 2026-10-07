<?php

use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\ActivityLog;

middleware('auth');
name('settings.activity');

new class extends Component
{
    use WithPagination;

    public string $filterAction = '';
    public string $search = '';

    public function with(): array
    {
        $query = auth()->user()->activityLogs()
            ->orderBy('created_at', 'desc');

        if ($this->filterAction) {
            $query->where('action', $this->filterAction);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('action', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'activities' => $query->paginate(15),
            'actionTypes' => auth()->user()->activityLogs()
                ->select('action')
                ->distinct()
                ->pluck('action')
                ->toArray(),
        ];
    }

    public function clearFilters()
    {
        $this->filterAction = '';
        $this->search = '';
        $this->resetPage();
    }

    public function getActivityIcon($action)
    {
        return match(true) {
            str_contains($action, 'password') => 'phosphor-lock-duotone',
            str_contains($action, 'email') => 'phosphor-envelope-duotone',
            str_contains($action, 'api') => 'phosphor-code-duotone',
            str_contains($action, 'login') => 'phosphor-sign-in-duotone',
            str_contains($action, 'profile') => 'phosphor-user-duotone',
            str_contains($action, 'subscription') => 'phosphor-credit-card-duotone',
            str_contains($action, 'delete') => 'phosphor-trash-duotone',
            default => 'phosphor-clock-duotone',
        };
    }

    public function getActivityColor($action)
    {
        return match(true) {
            str_contains($action, 'delete') => 'ui:bg-high-soft ui:text-high',
            str_contains($action, 'password') || str_contains($action, 'security') => 'ui:bg-medium-soft ui:text-medium',
            str_contains($action, 'login') => 'ui:bg-low-soft ui:text-low',
            default => 'ui:bg-brand-50 ui:text-brand-500',
        };
    }
};

?>

<x-layouts.app>
    @volt('settings.activity')
        <div class="">
            <x-app.settings-layout
                :title="__('Activity Log')"
                :description="__('View your account activity history and security events.')">

                {{-- Filters: server-side, live --}}
                <x-ui.card>
                    <div class="ui:flex ui:flex-col ui:gap-4 ui:sm:flex-row ui:sm:items-end">
                        <x-ui.field :label="__('Search')" for="search" class="ui:flex-1">
                            <x-ui.input wire:model.live.debounce.300ms="search" id="search" :placeholder="__('Search activities...')"/>
                        </x-ui.field>
                        <x-ui.field :label="__('Filter by Action')" for="filterAction" class="ui:flex-1">
                            <x-ui.select wire:model.live="filterAction" id="filterAction">
                                <option value="">{{ __('All Actions') }}</option>
                                @foreach($actionTypes as $type)
                                    <option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        @if($filterAction || $search)
                            <x-ui.button variant="ghost" wire:click="clearFilters">{{ __('Clear') }}</x-ui.button>
                        @endif
                    </div>
                </x-ui.card>

                {{-- Activity list --}}
                <x-ui.card flush>
                    @if($activities->isEmpty())
                        <x-ui.empty icon="clock-counter-clockwise">
                            @if($filterAction || $search)
                                {{ __('No activity found. Try adjusting your filters.') }}
                            @else
                                {{ __('Your account activity will appear here.') }}
                            @endif
                        </x-ui.empty>
                    @else
                        <ul class="ui:m-0 ui:list-none ui:p-0">
                            @foreach($activities as $activity)
                                <li class="ui:flex ui:items-start ui:gap-4 ui:px-5 ui:py-4 @if(!$loop->first) ui:border-0 ui:border-t ui:border-solid ui:border-line @endif">
                                    <span class="ui:flex ui:size-10 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-full {{ $this->getActivityColor($activity->action) }}">
                                        <x-dynamic-component :component="$this->getActivityIcon($activity->action)" class="ui:size-5"/>
                                    </span>
                                    <div class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-0.5">
                                        <span class="ui:text-sm ui:font-semibold ui:text-ink">{{ ucwords(str_replace('_', ' ', $activity->action)) }}</span>
                                        @if($activity->description)
                                            <span class="ui:text-sm ui:text-slate-600">{{ $activity->description }}</span>
                                        @endif
                                        <span class="ui:flex ui:flex-wrap ui:gap-x-4 ui:gap-y-1 ui:text-xs ui:text-slate-400">
                                            <span class="ui:flex ui:items-center ui:gap-1">
                                                <x-phosphor-clock class="ui:size-3.5"/>
                                                {{ $activity->created_at->diffForHumans() }}
                                            </span>
                                            @if($activity->ip_address)
                                                <span class="ui:flex ui:items-center ui:gap-1">
                                                    <x-phosphor-globe class="ui:size-3.5"/>
                                                    {{ $activity->ip_address }}
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                    <time class="ui:shrink-0 ui:text-xs ui:tabular-nums ui:text-slate-500">{{ $activity->created_at->format('M j, Y g:i A') }}</time>
                                </li>
                            @endforeach
                        </ul>

                        <div class="ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-3">
                            {{ $activities->links() }}
                        </div>
                    @endif
                </x-ui.card>

                {{-- Security tip --}}
                <div class="ui:flex ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-blue-200 ui:bg-info-soft ui:p-4">
                    <x-phosphor-info class="ui:size-5 ui:shrink-0 ui:text-info"/>
                    <div class="ui:flex ui:flex-col ui:gap-1 ui:text-sm ui:text-blue-900">
                        <span class="ui:font-semibold">{{ __('Security Tip') }}</span>
                        <p class="ui:m-0">{{ __('Review your activity log regularly to ensure all actions were performed by you. If you notice any suspicious activity, change your password immediately and contact support.') }}</p>
                    </div>
                </div>
            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
