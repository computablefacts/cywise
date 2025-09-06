<?php

namespace App\Listeners;

use App\Enums\SshTraceStateEnum;
use App\Events\ConfigureHost;
use App\Events\CreateAsset;
use App\Events\PullServerInfos;
use Illuminate\Support\Str;
use Vanilo\Order\Models\FulfillmentStatus;

/** @deprecated */
class ConfigureHostListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof ConfigureHost)) {
            throw new \Exception('Invalid event type!');
        }

        $uid = $event->uid;
        $user = $event->user;
        $server = $event->server;

        $user->actAs(); // otherwise the tenant will not be properly set

        if ($server && !$server->isReady()) { // If multiple setup requests were sent, only process them till one succeeds

            $isOk = true;
            $ssh = $server->sshConnection($uid, $user);
            $diagnosis = $server->sshListDiagnosis($ssh);

            if (count($diagnosis) === 1 && (
                    Str::contains($diagnosis[0], 'sudo: command not found') ||
                    Str::contains($diagnosis[0], 'yunohost: command not found'))) { // Check if YunoHost is already installed. No? install it!

                $username = 'twr_admin';
                $domain = "{$server->domain()->name}";

                $isOk = $isOk && $server->sshInstallYunoHost($ssh, $domain, $username);

                $server->ssh_username = $username;
                $server->save();

                $ssh = $server->sshConnection($uid, $user);
                $isOk = $isOk && $server->sshMonitorServer($ssh);
            }

            // $ports = [25 /* email */, 389 /* ldap */, 587 /* email */, 853 /* ? */, 993 /* email */, 5222 /* xmpp client */, 5269 /* xmpp server */];
            $ports = [25 /* email */, 587 /* email */, 993 /* email */];

            foreach ($ports as $port) {
                $isOk = $isOk && $server->sshCloseTcpPort($ssh, $port);
            }

            // $ports = [53 /* dnsmasq */, 784 /* ? */, 853 /* ? */, 5353 /* avahi-daemon / bonjour protocol */];
            $ports = [];

            foreach ($ports as $port) {
                $isOk = $isOk && $server->sshCloseUdpPort($ssh, $port);
            }

            $isOk = $isOk && $server->sshReloadFirewall($ssh);

            foreach ($this->adversaryMeterIpAddresses() as $ip) {
                $isOk = $isOk && $server->sshDoWhitelistIpAddress($ssh, $ip);
            }
            foreach ($this->dockerIpAddresses() as $ip) {
                $isOk = $isOk && $server->sshDoWhitelistIpAddress($ssh, $ip);
            }

            $isOk = $isOk && $server->sshRestartFail2Ban($ssh);
            $isOk = $isOk && $server->sshDisableAdminConsole($ssh);

            if ($server->ip() !== '51.15.140.162'/** myapps.addapps.io */) { // otherwise towerify will die...

                // $isOk = $isOk && $server->sshRestartDocker($ssh);

                $ssh->newTrace(SshTraceStateEnum::IN_PROGRESS, 'Starting asset monitoring...');
                CreateAsset::dispatch($server->user()->first(), $server->ip(), true, [$server->name]);
                $ssh->newTrace(SshTraceStateEnum::DONE, 'Asset monitoring started.');
            }
            if ($isOk) {

                $server->is_ready = true;
                $server->save();

                $orderItem = $server->order?->orderItem->first();

                if ($orderItem) {

                    $orderItem->fulfillment_status = FulfillmentStatus::FULFILLED();
                    $orderItem->save();

                    // TODO : if all order items have now been fulfilled, mark the order as 'Completed'
                }

                PullServerInfos::dispatch($uid, $user, $server);
            }
        }
    }

    private function adversaryMeterIpAddresses(): array
    {
        return config('towerify.adversarymeter.ip_addresses');
    }

    private function dockerIpAddresses(): array
    {
        return ['172.16.0.0/12', '192.168.0.0/16', '10.0.0.0/8'];
    }
}
