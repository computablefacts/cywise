<?php

namespace App\Listeners;

use App\Events\CreateBackup;
use App\Models\YnhBackup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateBackupListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof CreateBackup)) {
            throw new \Exception('Invalid event type!');
        }

        $uid = $event->uid;
        $user = $event->user;
        $server = $event->server;

        $user->actAs(); // otherwise the tenant will not be properly set

        if ($server && $server->isReady()) {

            $ssh = $server->sshConnection($uid, $user);
            $result = $server->sshCreateBackup($ssh);

            if (count($result) > 0) {
                $backup = YnhBackup::create([
                    'ynh_server_id' => $server->id,
                    'user_id' => $user->id,
                    'name' => $result['name'],
                    'size' => $result['size'],
                    'result' => $result['results'],
                ]);
                if ($backup) {

                    // Download backup
                    $infile = "/home/yunohost.backup/archives/{$backup->name}.tar";
                    $outfile = "/tmp/{$backup->name}.tar";

                    // Copy backup to storage
                    if (!$ssh->download($infile, $outfile)) {
                        Log::error("[BACKUP] Downloading file from {$infile} to {$outfile} failed!");
                    } else {

                        $backup->storage_path = "{$server->id}/{$backup->name}.tar";

                        if (Storage::disk(config('filesystems.backups'))->putFileAs("{$server->id}", $outfile, "{$backup->name}.tar")) {
                            $backup->save();
                        } else {
                            Log::error("[BACKUP] Moving file from {$outfile} to {$backup->storage_path} failed!");
                        }
                        unlink($outfile);
                    }
                }
            }
        }
    }
}
