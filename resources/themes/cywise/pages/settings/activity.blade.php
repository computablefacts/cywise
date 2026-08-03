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
            str_contains($action, 'delete') => 'text-danger',
            str_contains($action, 'password') || str_contains($action, 'security') => 'text-warning',
            str_contains($action, 'login') => 'text-success',
            default => 'text-primary',
        };
    }
};

?>

<x-layouts.app>
    @volt('settings.activity')
        <div class="">
            <x-app.settings-layout
                title="Activity Log"
                description="View your account activity history and security events.">
                
                <div class="w-100 max-w-4xl">
                    
                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label for="search" class="form-label small fw-bold">Search</label>
                                    <input 
                                        wire:model.live.debounce.300ms="search"
                                        type="text" 
                                        id="search"
                                        placeholder="Search activities..."
                                        class="form-control form-control-sm"
                                    >
                                </div>

                                <div class="col-md-5">
                                    <label for="filterAction" class="form-label small fw-bold">Filter by Action</label>
                                    <select 
                                        wire:model.live="filterAction"
                                        id="filterAction"
                                        class="form-select form-select-sm"
                                    >
                                        <option value="">All Actions</option>
                                        @foreach($actionTypes as $type)
                                            <option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if($filterAction || $search)
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button 
                                            wire:click="clearFilters"
                                            class="btn btn-link btn-sm text-decoration-none"
                                        >
                                            Clear
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Activity List -->
                    <div class="card">
                        <div class="card-body">
                            @if($activities->isEmpty())
                                <div class="py-5 text-center">
                                    <svg class="mb-3 text-muted" style="width: 64px; height: 64px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h5 class="fw-bold">No activity found</h5>
                                    <p class="text-muted small">
                                        @if($filterAction || $search)
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            Your account activity will appear here.
                                        @endif
                                    </p>
                                </div>
                            @else
                                <div class="list-group list-group-flush">
                                    @foreach($activities as $activity)
                                        <div class="list-group-item px-0 py-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <x-dynamic-component 
                                                            :component="$this->getActivityIcon($activity->action)" 
                                                            class="{{ $this->getActivityColor($activity->action) }}"
                                                            style="width: 20px; height: 20px;"
                                                        />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <p class="mb-0 fw-bold small">
                                                                {{ ucwords(str_replace('_', ' ', $activity->action)) }}
                                                            </p>
                                                            @if($activity->description)
                                                                <p class="mb-0 text-muted small">
                                                                    {{ $activity->description }}
                                                                </p>
                                                            @endif
                                                            <div class="d-flex flex-wrap mt-1">
                                                                <span class="text-muted small me-3">
                                                                    <svg class="me-1" style="width: 12px; height: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                    </svg>
                                                                    {{ $activity->created_at->diffForHumans() }}
                                                                </span>
                                                                @if($activity->ip_address)
                                                                    <span class="text-muted small">
                                                                        <svg class="me-1" style="width: 12px; height: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                                                        </svg>
                                                                        {{ $activity->ip_address }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <span class="text-muted small">
                                                            {{ $activity->created_at->format('M j, Y g:i A') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Pagination -->
                                <div class="mt-4">
                                    {{ $activities->links() }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Security Notice -->
                    <div class="alert alert-info mt-4" role="alert">
                        <div class="d-flex">
                            <svg class="flex-shrink-0 me-3 mt-1" style="width: 20px; height: 20px;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h6 class="alert-heading fw-bold mb-1">Security Tip</h6>
                                <p class="mb-0 small">
                                    Review your activity log regularly to ensure all actions were performed by you. If you notice any suspicious activity, change your password immediately and contact support.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
