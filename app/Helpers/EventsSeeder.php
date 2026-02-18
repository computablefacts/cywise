<?php

namespace App\Helpers;

use App\Hashing\TwHasher;
use App\Models\User;
use App\Models\YnhServer;
use Illuminate\Support\Collection;
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
        $user = User::query()->where('email', '=', $email)->first();

        if ($user) {
            return $user;
        }

        /** @var User $user */
        $user = User::factory([
            'name' => $name,
            'email' => $email,
            'password' => TwHasher::hash($password),
        ])->admin()->create();

        return $user;
    }

    public static function findOrCreateServers(int $count = self::SERVERS_COUNT): Collection
    {
        $user = self::getDismissTestUser();
        $existingServersCount = YnhServer::query()
            ->where('created_by', '=', $user->id)
            ->count();

        if ($existingServersCount < $count) {
            YnhServer::factory()
                ->count($count - $existingServersCount)
                ->state(fn(array $attributes) => [
                    'created_by' => $user->id,
                ])
                ->create();
        }

        $servers = YnhServer::query()
            ->where('created_by', '=', $user->id)
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
