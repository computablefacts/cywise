<?php

namespace App\Providers;

use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Listeners\SamlEventSubscriber;
use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\AssetTagHash;
use App\Models\Chunk;
use App\Models\ChunkTag;
use App\Models\Collection;
use App\Models\Conversation;
use App\Models\File;
use App\Models\HiddenAlert;
use App\Models\Honeypot;
use App\Models\Prompt;
use App\Models\ScheduledTask;
use App\Models\Table;
use App\Models\Template;
use App\Models\Trial;
use App\Models\Vector;
use App\Models\YnhServer;
use App\Observers\AssetObserver;
use App\Observers\AssetTagHashObserver;
use App\Observers\AssetTagObserver;
use App\Observers\ChunkObserver;
use App\Observers\ChunkTagObserver;
use App\Observers\CollectionObserver;
use App\Observers\ConversationObserver;
use App\Observers\FilesObserver;
use App\Observers\HiddenAlertObserver;
use App\Observers\HoneypotObserver;
use App\Observers\PromptObserver;
use App\Observers\ScheduledTaskObserver;
use App\Observers\TableObserver;
use App\Observers\TemplateObserver;
use App\Observers\TrialObserver;
use App\Observers\VectorObserver;
use App\Observers\YnhServerObserver;
use App\Rules\AtLeastOneDigit;
use App\Rules\AtLeastOneLetter;
use App\Rules\AtLeastOneLowercaseLetter;
use App\Rules\AtLeastOneUppercaseLetter;
use App\Rules\OnlyLettersAndDigits;
use Exception;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // AdversaryMeter
        $this->app->bind('am_api_utils', function () {
            return new \App\Helpers\VulnerabilityScannerApiUtils();
        });

        // CyberBuddy
        $this->app->bind('cb_api_utils', function () {
            return new \App\Helpers\ApiUtils();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment() == 'production') {
            $this->app['request']->server->set('HTTPS', true);
        }

        Password::defaults(
            Password::min(12)
                ->max(100)
                ->rules([
                    new OnlyLettersAndDigits,
                    new AtLeastOneLetter,
                    new AtLeastOneDigit,
                    new AtLeastOneUppercaseLetter,
                    new AtLeastOneLowercaseLetter,
                ])
        );

        $this->setSchemaDefaultLength();

        // Register activity log event listeners
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);

        Validator::extend('base64image', function ($attribute, $value, $parameters, $validator) {
            $explode = explode(',', $value);
            $allow = ['png', 'jpg', 'svg', 'jpeg'];
            $format = str_replace(
                [
                    'data:image/',
                    ';',
                    'base64',
                ],
                [
                    '', '', '',
                ],
                $explode[0]
            );

            // check file format
            if (!in_array($format, $allow)) {
                return false;
            }

            // check base64 format
            if (!preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $explode[1])) {
                return false;
            }

            return true;
        });

        RateLimiter::for('api', function (Request $request) {
            Log::info("RateLimiter triggered!");
            return Limit::perMinute(60)->by($request->user()?->id ?: (request()->header('CF-Connecting-IP') ?? request()->ip()));
        });

        Log::info("RateLimiter loaded!");

        YnhServer::observe(YnhServerObserver::class);

        // AdversaryMeter
        Asset::observe(AssetObserver::class);
        AssetTagHash::observe(AssetTagHashObserver::class);
        AssetTag::observe(AssetTagObserver::class);
        HiddenAlert::observe(HiddenAlertObserver::class);
        Honeypot::observe(HoneypotObserver::class);
        Trial::observe(TrialObserver::class);

        // CyberBuddy
        Chunk::observe(ChunkObserver::class);
        ChunkTag::observe(ChunkTagObserver::class);
        Collection::observe(CollectionObserver::class);
        Conversation::observe(ConversationObserver::class);
        File::observe(FilesObserver::class);
        Prompt::observe(PromptObserver::class);
        // RemoteAction::observe(RemoteActionObserver::class);
        ScheduledTask::observe(ScheduledTaskObserver::class);
        Table::observe(TableObserver::class);
        Template::observe(TemplateObserver::class);
        Vector::observe(VectorObserver::class);

        // SAML
        Event::subscribe(SamlEventSubscriber::class);
    }

    private function setSchemaDefaultLength(): void
    {
        try {
            Schema::defaultStringLength(191);
        } catch (Exception $exception) {
        }
    }
}
