<?php

namespace App\Providers;

use App\Events\AddTwrUserPermission;
use App\Events\AddUserPermission;
use App\Events\BeginPortsScan;
use App\Events\BeginVulnsScan;
use App\Events\ConfigureHost;
use App\Events\CreateAsset;
use App\Events\CreateBackup;
use App\Events\DeleteAsset;
use App\Events\EndPortsScan;
use App\Events\EndVulnsScan;
use App\Events\ImportTable;
use App\Events\ImportVirtualTable;
use App\Events\IngestFile;
use App\Events\IngestHoneypotsEvents;
use App\Events\InstallApp;
use App\Events\ProcessLogalertPayload;
use App\Events\ProcessLogalertPayloadEx;
use App\Events\ProcessLogparserPayload;
use App\Events\PullServerInfos;
use App\Events\RebuildLatestEventsCache;
use App\Events\RebuildPackagesList;
use App\Events\RemoveUserPermission;
use App\Events\SendAuditReport;
use App\Events\StartAssetsDiscover;
use App\Events\UninstallApp;
use App\Listeners\AddTwrUserPermissionListener;
use App\Listeners\AddUserPermissionListener;
use App\Listeners\BeginPortsScanListener;
use App\Listeners\BeginVulnsScanListener;
use App\Listeners\ConfigureHostListener;
use App\Listeners\CreateAssetListener;
use App\Listeners\CreateBackupListener;
use App\Listeners\DeleteAssetListener;
use App\Listeners\EndPortsScanListener;
use App\Listeners\EndVulnsScanListener;
use App\Listeners\ImportTableListener;
use App\Listeners\ImportVirtualTableListener;
use App\Listeners\IngestFileListener;
use App\Listeners\IngestHoneypotsEventsListener;
use App\Listeners\InstallAppListener;
use App\Listeners\LoginFailedListener;
use App\Listeners\LoginSucceededListener;
use App\Listeners\LogoutSucceededListener;
use App\Listeners\ProcessLogalertPayloadListener;
use App\Listeners\ProcessLogalertPayloadListenerEx;
use App\Listeners\ProcessLogparserPayloadListener;
use App\Listeners\RebuildLatestEventsCacheListener;
use App\Listeners\RebuildPackagesListListener;
use App\Listeners\RemoveUserPermissionListener;
use App\Listeners\SendAuditReportListener;
use App\Listeners\StartAssetsDiscoverListener;
use App\Listeners\StoreLoginTime;
use App\Listeners\UninstallAppListener;
use App\Listeners\UpdateServerInfosListener;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ConfigureHost::class => [
            ConfigureHostListener::class,
        ],
        InstallApp::class => [
            InstallAppListener::class,
        ],
        UninstallApp::class => [
            UninstallAppListener::class,
        ],
        AddUserPermission::class => [
            AddUserPermissionListener::class,
        ],
        AddTwrUserPermission::class => [
            AddTwrUserPermissionListener::class,
        ],
        RemoveUserPermission::class => [
            RemoveUserPermissionListener::class,
        ],
        CreateBackup::class => [
            CreateBackupListener::class,
        ],
        PullServerInfos::class => [
            UpdateServerInfosListener::class,
        ],
        ProcessLogalertPayload::class => [
            ProcessLogalertPayloadListener::class,
        ],
        ProcessLogalertPayloadEx::class => [
            ProcessLogalertPayloadListenerEx::class,
        ],
        ProcessLogparserPayload::class => [
            ProcessLogparserPayloadListener::class,
        ],
        RebuildPackagesList::class => [
            RebuildPackagesListListener::class,
        ],
        RebuildLatestEventsCache::class => [
            RebuildLatestEventsCacheListener::class,
        ],

        // AdversaryMeter
        BeginPortsScan::class => [
            BeginPortsScanListener::class,
        ],
        EndPortsScan::class => [
            EndPortsScanListener::class,
        ],
        BeginVulnsScan::class => [
            BeginVulnsScanListener::class,
        ],
        EndVulnsScan::class => [
            EndVulnsScanListener::class,
        ],
        CreateAsset::class => [
            CreateAssetListener::class,
        ],
        DeleteAsset::class => [
            DeleteAssetListener::class,
        ],
        IngestHoneypotsEvents::class => [
            IngestHoneypotsEventsListener::class,
        ],
        SendAuditReport::class => [
            SendAuditReportListener::class,
        ],

        // CyberBuddy
        IngestFile::class => [
            IngestFileListener::class,
        ],
        ImportTable::class => [
            ImportTableListener::class,
        ],
        ImportVirtualTable::class => [
            ImportVirtualTableListener::class,
        ],

        // Check
        StartAssetsDiscover::class => [
            StartAssetsDiscoverListener::class,
        ],

        // Laravel
        Failed::class => [
            LoginFailedListener::class,
        ],
        Login::class => [
            LoginSucceededListener::class,
            StoreLoginTime::class,
        ],
        Logout::class => [
            LogoutSucceededListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
