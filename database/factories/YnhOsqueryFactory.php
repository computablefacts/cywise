<?php

namespace Database\Factories;

use App\Models\YnhOsquery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\YnhOsquery>
 */
class YnhOsqueryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dateTime = $this->faker->dateTimeBetween('-24 hour', 'now');
        $columns = [
            'arch' => 'x86_64',
            'build' => null,
            'codename' => 'bullseye',
            'major' => 11,
            'minor' => 0,
            'name' => 'Debian GNU/Linux',
            'patch' => 0,
            'platform' => 'debian',
            'platform_like' => null,
            'version' => '11 (bullseye)',
        ];
        $columnsJson = json_encode($columns);

        return [
            'created_at' => $dateTime,
            'updated_at' => $dateTime,
            'ynh_server_id' => $this->faker->numberBetween(1, 10),
            'row' => 0,
            'name' => 'os_version',
            'host_identifier' => 'my_server',
            'calendar_time' => $dateTime->format('Y-m-d H:i:s'),
            'unix_time' => $dateTime->getTimestamp(),
            'epoch' => 0,
            'counter' => $this->faker->numberBetween(1, 10),
            'numerics' => 0,
            'columns' => $columnsJson,
            'action' => 'snapshot',
        ];
    }

    public function loginOrLogout(): Factory
    {
        $columns = ['username' => $this->faker->randomElement(['terry.keagan', 'brandy41', 'fkrajcik'])];
        return $this->state(function (array $attributes) use ($columns) {
            return [
                'name' => 'last',
                'action' => $this->faker->randomElement(['added', 'removed']),
                'columns' => $columns,
            ];
        });
    }

    public function authorizedKey(): Factory
    {
        $columns = ['key_file' => '/root/.ssh/authorized_keys'];
        return $this->state(function (array $attributes) use ($columns) {
            return [
                'name' => 'authorized_keys',
                'action' => $this->faker->randomElement(['added', 'removed']),
                'columns' => $columns,
            ];
        });
    }

    public function userSshKey(): Factory
    {
        $user = $this->faker->randomElement(['terry.keagan', 'brandy41', 'fkrajcik']);
        $keyname = $this->faker->randomElement(['id_restic_ed25519', 'id_rsa']);
        $columns = [
            'username' => $user,
            'path' => "/$user/.ssh/$keyname",
        ];
        return $this->state(function (array $attributes) use ($columns) {
            return [
                'name' => 'user_ssh_keys',
                'action' => $this->faker->randomElement(['added', 'removed']),
                'columns' => $columns,
            ];
        });
    }

    public function userAccount(): Factory
    {
        $user = $this->faker->randomElement(['terry.keagan', 'brandy41', 'fkrajcik']);
        $columns = [
            'username' => $user,
            'directory' => "/home/$user",
        ];
        return $this->state(function (array $attributes) use ($columns) {
            return [
                'name' => 'users',
                'action' => $this->faker->randomElement(['added', 'removed']),
                'columns' => $columns,
            ];
        });
    }

    public function group(): Factory
    {
        $groupName = $this->faker->randomElement(['adm', 'jenkins', 'nginx']);
        $columns = ['groupname' => $groupName];
        return $this->state(function (array $attributes) use ($columns) {
            return [
                'name' => 'groups',
                'action' => $this->faker->randomElement(['added', 'removed']),
                'columns' => $columns,
            ];
        });
    }

    public function package(): Factory
    {
        $name = $this->faker->randomElement(['corepack', 'npm']);
        $version = $this->faker->randomElement(['2.0', '2.1']);
        $columns = [
            'name' => $name,
            'version' => $version,
        ];
        return $this->state(function (array $attributes) use ($columns) {
            return [
                'name' => 'npm_packages', // Other package types exist like win_packages, python_packages, etc
                'action' => $this->faker->randomElement(['added', 'removed']),
                'columns' => $columns,
            ];
        });
    }

    public function suidBin(): Factory
    {
        $path = $this->faker->randomElement(['/usr/bin/write', '/usr/bin/sg']);
        $columns = ['path' => $path];
        return $this->state(function (array $attributes) use ($columns) {
            return [
                'name' => 'suid_bin',
                'action' => $this->faker->randomElement(['added', 'removed']),
                'columns' => $columns,
            ];
        });
    }

    public function ldPreload(): Factory
    {
        $path = $this->faker->randomElement(['/usr/lib/firefox-esr/firefox-esr', '/opt/firefox-133.0/firefox-bin']);
        $columns = [
            'path' => $path,
            'value' => 'libmozsandbox.so',
        ];
        return $this->state(function (array $attributes) use ($columns) {
            return [
                'name' => 'ld_preload',
                'action' => 'snapshot',
                'columns' => $columns,
            ];
        });
    }

    public function kernelModule(): Factory
    {
        $name = $this->faker->randomElement(['scsi_mod', 'crc32c_intel', 'ata_piix']);
        $columns = ['name' => $name];
        return $this->state(function (array $attributes) use ($columns) {
            return [
                'name' => 'kernel_modules',
                'action' => $this->faker->randomElement(['added', 'removed']),
                'columns' => $columns,
            ];
        });
    }
}
