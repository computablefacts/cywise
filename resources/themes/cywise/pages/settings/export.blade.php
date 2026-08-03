<?php
    use Filament\Notifications\Notification;
    use Livewire\Volt\Component;
    use function Laravel\Folio\{middleware, name};
    use App\Models\ActivityLog;
    use Wave\Post;
    use Wave\ApiKey;
    
    middleware('auth');
    name('settings.export');

	new class extends Component
	{
        public function exportData()
        {
            $user = auth()->user();
            
            // Gather all user data
            $data = [
                'exported_at' => now()->toDateTimeString(),
                'profile' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar(),
                    'verified' => $user->verified,
                    'created_at' => $user->created_at->toDateTimeString(),
                    'updated_at' => $user->updated_at->toDateTimeString(),
                ],
                'profile_fields' => $user->keyValues ? $user->keyValues->map(function ($kv) {
                    return [
                        'key' => $kv->key,
                        'value' => $kv->value,
                    ];
                })->toArray() : [],
                'privacy_settings' => $user->privacy_settings ?? [],
                'notification_preferences' => $user->notification_preferences ?? [],
                'social_links' => $user->social_links ?? [],
                'activity_logs' => $user->activityLogs()->orderBy('created_at', 'desc')->get()->map(function ($log) {
                    return [
                        'action' => $log->action,
                        'description' => $log->description,
                        'ip_address' => $log->ip_address,
                        'metadata' => $log->metadata,
                        'created_at' => $log->created_at->toDateTimeString(),
                    ];
                })->toArray(),
                'api_keys' => $user->apiKeys()->get()->map(function ($key) {
                    return [
                        'name' => $key->name,
                        'key' => substr($key->key, 0, 10) . '...' . substr($key->key, -5), // Partially masked
                        'last_used_at' => $key->last_used_at ? $key->last_used_at->toDateTimeString() : null,
                        'created_at' => $key->created_at->toDateTimeString(),
                    ];
                })->toArray(),
                'blog_posts' => Post::where('author_id', $user->id)->get()->map(function ($post) {
                    return [
                        'title' => $post->title,
                        'slug' => $post->slug,
                        'excerpt' => $post->excerpt,
                        'status' => $post->status,
                        'featured' => $post->featured,
                        'category' => $post->category ? $post->category->name : null,
                        'created_at' => $post->created_at->toDateTimeString(),
                        'updated_at' => $post->updated_at->toDateTimeString(),
                    ];
                })->toArray(),
            ];
            
            // Add subscription data if available
            if ($user->subscription) {
                $subscription = $user->subscription;
                $data['subscription'] = [
                    'plan' => $subscription->plan->name ?? null,
                    'status' => $subscription->status,
                    'cycle' => $subscription->cycle ?? null,
                    'created_at' => $subscription->created_at instanceof \Carbon\Carbon 
                        ? $subscription->created_at->toDateTimeString() 
                        : $subscription->created_at,
                    'ends_at' => $subscription->ends_at 
                        ? ($subscription->ends_at instanceof \Carbon\Carbon 
                            ? $subscription->ends_at->toDateTimeString() 
                            : $subscription->ends_at)
                        : null,
                ];
            } else {
                $data['subscription'] = null;
            }
            
            // Add roles and permissions
            $data['roles'] = $user->roles->pluck('name')->toArray();
            $data['permissions'] = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Log the export
            ActivityLog::log('data_exported', 'User data exported');
            
            // Generate JSON file
            $filename = 'user-data-' . $user->username . '-' . now()->format('Y-m-d-His') . '.json';
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            // Return as download
            return response()->streamDownload(function () use ($json) {
                echo $json;
            }, $filename, [
                'Content-Type' => 'application/json',
            ]);
        }
	}
?>

<x-layouts.app>
    @volt('settings.export') 
        <div class="">
            <x-app.settings-layout
                title="Export Data"
                description="Download a copy of all your data stored in our system."
            >
                <div class="w-100 max-w-lg">
                    <!-- Export Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <svg class="text-primary" style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                    </svg>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-2">Download Your Data</h5>
                                    <p class="small text-muted mb-3">
                                        Export a complete copy of your account data including:
                                    </p>
                                    <ul class="mb-3 small text-muted">
                                        <li>Profile information and settings</li>
                                        <li>Activity logs and account history</li>
                                        <li>Blog posts you've authored</li>
                                        <li>API keys (partially masked)</li>
                                        <li>Privacy and notification preferences</li>
                                        <li>Subscription information</li>
                                        <li>Roles and permissions</li>
                                    </ul>
                                    <p class="small text-muted">
                                        Your data will be exported in JSON format for easy processing and portability.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button 
                                    wire:click="exportData"
                                    type="button"
                                    class="btn btn-primary btn-sm"
                                >
                                    <svg class="me-2" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Export My Data
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- GDPR Info -->
                    <div class="alert alert-info mb-4" role="alert">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <svg class="text-info" style="width: 20px; height: 20px;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h6 class="alert-heading fw-bold small">Data Privacy</h6>
                                <p class="mb-0 small">
                                    This feature complies with GDPR data portability requirements. Your data export will be logged in your activity history for security purposes.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Actions -->
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold small mb-2">Need to delete your account?</h6>
                            <p class="mb-0 small text-muted">
                                If you'd like to permanently delete your account and all associated data, visit the 
                                <a href="{{ route('settings.deletion') }}" class="text-primary text-decoration-none fw-medium">Account Security</a> page.
                            </p>
                        </div>
                    </div>
                </div>
            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
