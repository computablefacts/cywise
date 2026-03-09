<?php

namespace Database\Seeders;

use App\Enums\OsqueryPlatformEnum;
use App\Models\User;
use App\Models\YnhServer;
use Illuminate\Database\Seeder;

/**
 * Dev-only seeder: creates local Linux and Windows test servers so you can
 * retrieve their install scripts via the /update/{secret} route without
 * needing a real Sanctum token or a live server.
 *
 * Usage:
 *   php artisan db:seed --class=YnhServerDevSeeder
 *
 * Then fetch the scripts:
 *   curl http://localhost/update/dev-linux-secret    # bash script
 *   curl http://localhost/update/dev-windows-secret  # PowerShell script
 */
class YnhServerDevSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', config('towerify.admin.email'))->firstOrFail();

        YnhServer::updateOrCreate(
            ['secret' => 'dev-linux-secret'],
            [
                'name' => 'dev-linux-server',
                'ip_address' => '127.0.0.1',
                'created_by' => $admin->id,
                'platform' => OsqueryPlatformEnum::LINUX,
                'is_ready' => true,
                'is_frozen' => false,
            ]
        );

        YnhServer::updateOrCreate(
            ['secret' => 'dev-windows-secret'],
            [
                'name' => 'dev-windows-server',
                'ip_address' => '127.0.0.2',
                'created_by' => $admin->id,
                'platform' => OsqueryPlatformEnum::WINDOWS,
                'is_ready' => true,
                'is_frozen' => false,
            ]
        );
    }
}
