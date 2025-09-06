<?php

namespace App\Listeners;

use App\Events\RebuildPackagesList;
use App\Models\YnhCve;
use App\Models\YnhOsquery;
use App\Models\YnhOsqueryPackage;
use App\Models\YnhServer;
use Illuminate\Support\Facades\Log;

class RebuildPackagesListListener extends AbstractListener
{
    public function viaQueue(): string
    {
        return self::MEDIUM;
    }

    protected function handle2($event)
    {
        if (!($event instanceof RebuildPackagesList)) {
            throw new \Exception('Invalid event type!');
        }

        $user = $event->user;
        $server = $event->server;

        $user->actAs(); // otherwise the tenant will not be properly set

        if (!$server) {
            Log::info("Loading servers for user {$event->user->email}...");
            YnhServer::all()->each(fn(YnhServer $server) => RebuildPackagesList::dispatch($event->user, $server));
        } else {
            Log::info("Processing server {$server->name} for user {$event->user->email}...");
            try {

                Log::info("Processing packages for server {$server->name}...");
                $osInfo = YnhOsquery::osInfos(collect([$server]))->first();

                if (!$osInfo) {
                    Log::info("No OS version found for server {$server->name}");
                } else {

                    Log::info("OS version is {$osInfo->os}/{$osInfo->codename}");
                    YnhOsqueryPackage::where('ynh_server_id', $server->id)->delete();

                    /** @var YnhOsquery $latest */
                    $latest = YnhOsquery::where('ynh_server_id', $server->id)
                        ->where('name', 'deb_packages_installed_snapshot')
                        ->orderBy('calendar_time', 'desc')
                        ->first();

                    if ($latest) { // use our debian-specific implementation because we deal with apt, snap, dpkg, etc.
                        $installed = YnhOsquery::where('ynh_server_id', $server->id)
                            ->where('name', 'deb_packages_installed_snapshot')
                            ->where('unix_time', $latest->unix_time)
                            ->where('calendar_time', $latest->calendar_time)
                            ->whereJsonContains('columns', ['uid' => $latest->columns['uid']])
                            ->orderBy('calendar_time', 'desc')
                            ->get();
                    } else {

                        // The list of uninstalled packages
                        $uninstalled = YnhOsquery::where('ynh_server_id', $server->id)
                            ->where('name', 'deb_packages')
                            ->where('action', 'removed')
                            ->orderBy('calendar_time', 'desc')
                            ->get()
                            ->groupBy(fn(YnhOsquery $event) => $event->columns['name'] . $event->columns['version']);

                        // The list of installed packages
                        $installed = YnhOsquery::where('ynh_server_id', $server->id)
                            ->where('name', 'deb_packages')
                            ->where('action', 'added')
                            ->orderBy('calendar_time', 'desc')
                            ->get()
                            ->filter(function (YnhOsquery $event) use ($uninstalled) { // filter out installed then uninstalled packages

                                $key = $event->columns['name'] . $event->columns['version'];

                                if (isset($uninstalled[$key])) {
                                    return !collect($uninstalled[$key])->contains(fn(YnhOsquery $e) => $e->calendar_time->isAfter($event->calendar_time));
                                }
                                return true;
                            });
                    }

                    Log::info("{$installed->count()} packages installed");

                    // Save snapshot!
                    $installed->each(function (YnhOsquery $event) use ($server, $osInfo) {

                        $cves = YnhCve::appCves($osInfo->os, $osInfo->codename, $event->columns['name'], $event->columns['version']);

                        if ($cves->isEmpty()) {
                            YnhOsqueryPackage::create([
                                'ynh_server_id' => $server->id,
                                'ynh_cve_id' => null,
                                'os' => $osInfo->os,
                                'os_version' => $osInfo->codename,
                                'package' => $event->columns['name'],
                                'package_version' => $event->columns['version'],
                                'cves' => [],
                            ]);
                        } else {
                            $cves->each(function (YnhCve $cve) use ($server, $osInfo, $event, $cves) {
                                YnhOsqueryPackage::create([
                                    'ynh_server_id' => $server->id,
                                    'ynh_cve_id' => $cve->id,
                                    'os' => $osInfo->os,
                                    'os_version' => $osInfo->codename,
                                    'package' => $event->columns['name'],
                                    'package_version' => $event->columns['version'],
                                    'cves' => $cves->pluck('id')->toArray(),
                                ]);
                            });
                        }
                    });
                }
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
            }
        }
    }
}
