<?php

namespace App\Listeners;

use App\Enums\SshTraceStateEnum;
use App\Events\CreateAsset;
use App\Events\InstallApp;
use App\Events\PullServerInfos;
use App\Helpers\AppStore;
use App\Models\YnhApplication;

class InstallAppListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof InstallApp)) {
            throw new \Exception('Invalid event type!');
        }

        $uid = $event->uid;
        $user = $event->user;
        $server = $event->server;
        $order = $event->order;

        $user->actAs(); // otherwise the tenant will not be properly set

        if ($server && $server->isReady()) {

            $domain = "{$order->sku()}.{$server->domain()->name}";
            $ssh = $server->sshConnection($uid, $user);

            // https://forum.yunohost.org/t/mattermost-chat-group-discussion/4256/41?page=3
            if ($order->sku() === 'mattermost' || $order->sku() === 'paperless-ngx') {
                $ynhPassword = preg_replace('/\W/', '', $user->ynhPassword());
            } else {
                $ynhPassword = $user->ynhPassword();
            }

            $isOk = $server->sshUpdateAptCache($ssh);
            $isOk = $isOk && $server->sshCreateDomain($ssh, $domain);
            $isOk = $isOk && $server->sshUpdateDnsRecords($ssh);
            $isOk = $isOk && $server->sshInstallSslCertificates($ssh, $domain);
            $isOk = $isOk && $server->sshCreateOrUpdateUserProfile($ssh, $user->name, $user->email, $user->ynhUsername(), $user->ynhPassword());
            $isOk = $isOk && $server->sshInstallApp($ssh, $domain, $order->sku(), $user->ynhUsername(), $ynhPassword);

            $app = AppStore::findAppFromSku($order->sku())->firstOrFail();

            YnhApplication::updateOrCreate([
                'ynh_server_id' => $server->id,
                'sku' => $order->sku(),
            ], [
                'name' => $app['name'],
                'description' => '',
                'version' => '',
                'path' => $domain,
                'sku' => $order->sku(),
                'ynh_server_id' => $server->id,
                'ynh_order_id' => $order->id,
            ]);

            if ($isOk) {
                PullServerInfos::dispatch($uid, $user, $server);
            }

            $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Starting asset monitoring...');
            CreateAsset::dispatch($server->user()->first(), $domain, true, [$server->name]);
            $ssh->newTrace(SshTraceStateEnum::DONE, 'Asset monitoring started.');
        }
    }
}
