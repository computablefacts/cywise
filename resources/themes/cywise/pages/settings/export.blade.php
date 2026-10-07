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
                :title="__('Export Data')"
                :description="__('Download a copy of all your data stored in our system.')"
            >
                <div class="ui:flex ui:max-w-2xl ui:flex-col ui:gap-6">

                    {{-- Export: streams a JSON file --}}
                    <x-ui.card :title="__('Download Your Data')" :subtitle="__('Export a complete copy of your account data including:')">
                        <div class="ui:flex ui:flex-col ui:gap-4 ui:text-sm ui:text-slate-600">
                            <ul class="ui:m-0 ui:flex ui:flex-col ui:gap-1 ui:pl-5">
                                <li>{{ __('Profile information and settings') }}</li>
                                <li>{{ __('Activity logs and account history') }}</li>
                                <li>{{ __("Blog posts you've authored") }}</li>
                                <li>{{ __('API keys (partially masked)') }}</li>
                                <li>{{ __('Privacy and notification preferences') }}</li>
                                <li>{{ __('Subscription information') }}</li>
                                <li>{{ __('Roles and permissions') }}</li>
                            </ul>
                            <p class="ui:m-0">{{ __('Your data will be exported in JSON format for easy processing and portability.') }}</p>
                            <div>
                                <x-ui.button wire:click="exportData" icon="download-simple">{{ __('Export My Data') }}</x-ui.button>
                            </div>
                        </div>
                    </x-ui.card>

                    {{-- GDPR info --}}
                    <div class="ui:flex ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-blue-200 ui:bg-info-soft ui:p-4">
                        <x-phosphor-info class="ui:size-5 ui:shrink-0 ui:text-info"/>
                        <div class="ui:flex ui:flex-col ui:gap-1 ui:text-sm ui:text-blue-900">
                            <span class="ui:font-semibold">{{ __('Data Privacy') }}</span>
                            <p class="ui:m-0">{{ __('This feature complies with GDPR data portability requirements. Your data export will be logged in your activity history for security purposes.') }}</p>
                        </div>
                    </div>

                    {{-- Link to account deletion --}}
                    <x-ui.card :title="__('Need to delete your account?')">
                        <p class="ui:m-0 ui:text-sm ui:text-slate-600">
                            {!! __("If you'd like to permanently delete your account and all associated data, visit the :link page.", [
                                'link' => '<a href="' . e(route('settings.deletion')) . '" class="ui:font-medium ui:text-brand-600! ui:no-underline! ui:hover:text-brand-700!">' . e(__('Account Deletion')) . '</a>',
                            ]) !!}
                        </p>
                    </x-ui.card>
                </div>
            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
