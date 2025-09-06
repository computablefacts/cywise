<?php

namespace App\Listeners;

use App\Enums\SshTraceStateEnum;
use App\Events\DeleteAsset;
use App\Events\PullServerInfos;
use App\Events\UninstallApp;
use Illuminate\Support\Str;

class UninstallAppListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof UninstallApp)) {
            throw new \Exception('Invalid event type!');
        }

        $uid = $event->uid;
        $user = $event->user;
        $application = $event->application;
        $server = $event->application->server;

        $user->actAs(); // otherwise the tenant will not be properly set

        if ($server && $server->isReady()) {

            $domain = Str::before($application->path, '/');
            $ssh = $server->sshConnection($uid, $user);
            $isOk = $server->sshUninstallApp($ssh, $domain, $application->sku, $user->ynhUsername(), $user->ynhPassword());
            $isOk = $isOk && $server->sshRemoveDomain($ssh, $domain);
            $isOk = $isOk && $server->sshUpdateDnsRecords($ssh);

            if ($isOk) {
                PullServerInfos::dispatch($uid, $user, $server);
            }

            $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Stopping asset monitoring...');
            DeleteAsset::dispatch($user, $domain);
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Asset monitoring stopped.');
        }
    }
}
