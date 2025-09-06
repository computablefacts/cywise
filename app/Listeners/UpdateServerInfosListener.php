<?php

namespace App\Listeners;

use App\Events\PullServerInfos;

class UpdateServerInfosListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof PullServerInfos)) {
            throw new \Exception('Invalid event type!');
        }

        $uid = $event->uid;
        $user = $event->user;
        $server = $event->server;

        $user->actAs(); // otherwise the tenant will not be properly set

        if ($server && $server->isReady()) {
            $server->pullServerInfos($uid, $user);
        }
    }
}
