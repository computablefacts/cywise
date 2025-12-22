<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\YnhServer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\YnhBackup>
 */
class YnhBackupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ynh_server_id' => YnhServer::factory(),
            'user_id' => Auth::user()->id ?? User::factory(),
            'name' => $this->faker->word(),
            'size' => $this->faker->numberBetween(1000, 1000000),
            'storage_path' => $this->faker->optional()->filePath(),
            'result' => $this->getResult($this->faker->boolean(), $this->faker->boolean()),
        ];
    }

    // All success
    public function allSuccess(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'result' => $this->getResult(true, true),
            ];
        });
    }

    // One system error
    public function oneSystemError(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'result' => $this->getResult(false, true),
            ];
        });
    }

    // One app error
    public function oneAppError(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'result' => $this->getResult(true, false),
            ];
        });
    }

    private function getResult(bool $systemSuccess = true, bool $appsSuccess = true): array
    {
        $system = [
            'conf_manually_modified_files' => $systemSuccess ? 'Success' : 'Error',
            'data_mail' => 'Success',
            'data_home' => 'Success',
            'data_xmpp' => 'Success',
            'conf_ynh_certs' => 'Success',
            'conf_ynh_settings' => 'Success',
            'conf_ldap' => 'Success',
            'data_multimedia' => 'Success',
        ];

        $apps = [
            'phpmyadmin' => $appsSuccess ? 'Success' : 'Error',
            'jenkins' => 'Success',
            'portainer' => 'Success',
        ];

        return ['system' => $system, 'apps' => $apps];
    }
}
