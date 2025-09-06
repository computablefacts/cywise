<?php

namespace App\Listeners;

use App\Events\AddTwrUserPermission;
use App\Events\PullServerInfos;

class AddTwrUserPermissionListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof AddTwrUserPermission)) {
            throw new \Exception('Invalid event type!');
        }

        $uid = $event->uid;
        $user = $event->user;
        $server = $event->server;
        $user2 = $event->ynhUser;
        $permission = $event->permission;

        $user->actAs(); // otherwise the tenant will not be properly set

        if ($server && $server->isReady()) {

            $ssh = $server->sshConnection($uid, $user);
            $isOk = $server->sshCreateOrUpdateUserProfile($ssh, $user2->name, $user2->email, $user2->ynhUsername(), $user2->ynhPassword());
            $isOk = $isOk && $server->sshAddUserPermission($ssh, $user2->ynhUsername(), $permission);

            if ($isOk) {
                PullServerInfos::dispatch($uid, $user, $server);
            }
        }
    }
}
