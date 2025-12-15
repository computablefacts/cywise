<?php

namespace App\Providers;

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
use App\Models\Template;
use App\Models\Vector;
use App\Models\YnhBackup;
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
use App\Observers\TemplateObserver;
use App\Observers\VectorObserver;
use App\Observers\YnhBackupObserver;
use App\Observers\YnhServerObserver;
use App\Rules\AtLeastOneDigit;
use App\Rules\AtLeastOneLetter;
use App\Rules\AtLeastOneLowercaseLetter;
use App\Rules\AtLeastOneUppercaseLetter;
use App\Rules\OnlyLettersAndDigits;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
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
     *
     * @return void
     */
    public function boot()
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

        YnhBackup::observe(YnhBackupObserver::class);
        YnhServer::observe(YnhServerObserver::class);

        // AdversaryMeter
        Asset::observe(AssetObserver::class);
        AssetTagHash::observe(AssetTagHashObserver::class);
        AssetTag::observe(AssetTagObserver::class);
        HiddenAlert::observe(HiddenAlertObserver::class);
        Honeypot::observe(HoneypotObserver::class);

        // CyberBuddy
        Chunk::observe(ChunkObserver::class);
        ChunkTag::observe(ChunkTagObserver::class);
        Collection::observe(CollectionObserver::class);
        Conversation::observe(ConversationObserver::class);
        File::observe(FilesObserver::class);
        Prompt::observe(PromptObserver::class);
        Template::observe(TemplateObserver::class);
        Vector::observe(VectorObserver::class);

        // SAML
        Event::subscribe(SamlEventSubscriber::class);
    }

    private function setSchemaDefaultLength(): void
    {
        try {
            Schema::defaultStringLength(191);
        } catch (\Exception $exception) {
        }
    }
}
