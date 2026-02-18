<?php

namespace Database\Seeders;

use App\Models\YnhOsquery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RecentPackageEventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(int $serverId = null, int $count = 20): void
    {
        $server = RecentEventsHelper::findOrCreateOneServer($serverId);

        Log::debug("Create $count package events for server {$server->name}(id={$server->id})");
        for ($i = 0; $i < $count; $i++) {
            YnhOsquery::factory()
                ->package()
                ->state(fn(array $attributes) => [
                    'ynh_server_id' => $server->id,
                ])
                ->create();
        }
    }
}
