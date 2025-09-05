<?php

namespace App\Helpers;

use App\Hashing\TwHasher;
use App\Models\Role;
use App\Models\YnhServer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventsSeeder
{
    const int SERVERS_COUNT = 3;

    public static function getDismissTestUser(): User
    {
        return self::firstOrCreateDismissTestUser('Dismiss Test', 'dismiss@towerify.io', 'Demo-Pass');
    }

    private static function firstOrCreateDismissTestUser(string $name, string $email, string $password): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email' => $email,
                'password' => TwHasher::hash($password),
                'type' => 'admin',
            ]
        );

        self::addAdminRole($user);

        return $user;
    }

    private static function addAdminRole(User $dismissTestUser): void
    {
        $adminRole = Role::query()->where('name', Role::ADMIN)->first();

        if ($adminRole) {
            if (!DB::table('model_roles')
                ->where('role_id', $adminRole->id)
                ->where('model_id', $dismissTestUser->id)
                ->exists()) {
                DB::table('model_roles')
                    ->insert([
                        'role_id' => $adminRole->id,
                        'model_type' => User::class,
                        'model_id' => $dismissTestUser->id,
                    ]);
            }
        }
    }

    public static function findOrCreateServers(int $count = self::SERVERS_COUNT): Collection
    {
        $user = self::getDismissTestUser();
        $existingServersCount = YnhServer::query()
            ->where('user_id', '=', $user->id)
            ->count();

        if ($existingServersCount < $count) {
            YnhServer::factory()
                ->count($count - $existingServersCount)
                ->state(fn(array $attributes) => [
                    'user_id' => $user->id,
                ])
                ->create();
        }

        $servers = YnhServer::query()
            ->where('user_id', '=', $user->id)
            ->limit($count)
            ->get();

        return $servers;
    }

    public static function findOrCreateOneServer(int $serverId = null)
    {
        $server = YnhServer::query()->where('id', '=', $serverId)->first();

        if ($server === null) {
            Log::debug('No server provided: create one or use an existing one');
            $server = EventsSeeder::findOrCreateServers()->shuffle()->first();
        }

        return $server;
    }
}
