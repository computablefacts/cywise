<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filterBatch(function (Collection $entries) use ($isLocal) {
            return $isLocal || $entries->contains(function (IncomingEntry $entry) {
                    return $entry->isReportableException() ||
                        $entry->isFailedRequest() ||
                        $entry->isFailedJob() ||
                        $this->isLogEntry($entry) ||
                        $this->isSlowQuery($entry) ||
                        $this->isSlowRequest($entry) ||
                        $entry->isScheduledTask() ||
                        $entry->hasMonitoredTag();
                });
        });

        Telescope::tag(function (IncomingEntry $entry) {
            if ($entry->type === 'request') {
                return ['status:' . $entry->content['response_status']];
            }
            return [];
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $userGate, User $userTelescope) {
            Log::debug('userTelescope->email:' . $userTelescope->email);
            return Str::startsWith($userTelescope->email, $this->whitelistUsernames()) && Str::endsWith($userTelescope->email, $this->whitelistDomains());
        });
    }

    private function whitelistUsernames(): array
    {
        return collect(config('towerify.telescope.whitelist.usernames'))->map(fn(string $username) => $username . '@')->toArray();
    }

    private function whitelistDomains(): array
    {
        return collect(config('towerify.telescope.whitelist.domains'))->map(fn(string $domain) => '@' . $domain)->toArray();
    }

    private function isSlowQuery(IncomingEntry $entry): bool
    {
        return $entry->type === EntryType::QUERY && ($entry->content['slow'] ?? false);
    }

    private function isSlowRequest(IncomingEntry $entry): bool
    {
        return ($entry->type === EntryType::REQUEST) &&
            array_key_exists('duration', $entry->content) &&
            $entry->content['duration'] > 2500;
    }

    private function isLogEntry(IncomingEntry $entry): bool
    {
        return $entry->type === EntryType::LOG;
    }
}
