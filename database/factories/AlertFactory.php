<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $alertTypes = [
            'config_json_exposure_fuzz_v3_alert',
            'snmpv1_community_detect_string_v3_alert',
            'weak_credentials_found_for_ftp_v3_alert',
            'default_password_v3_alert',
            'CVE_2019_11248_v3_alert',
            'quickhits_file_v3_alert',
            'weak_cipher_suites_v3_alert',
            'self_signed_ssl_v3_alert',
            'mismatched_ssl_certificate_v3_alert',
            'expired_ssl_v3_alert',
            'wp_user_enum_v3_alert',
            'shell_history_v3_alert',
            'shellscripts_v3_alert',
            'error_logs_v3_alert',
            'phpinfo_files_v3_alert',
        ];

        return [
            'port_id' => Port::factory(),
            'type' => $this->faker->randomElement($alertTypes),
            'vulnerability' => $this->faker->sentence(),
            'remediation' => $this->faker->sentence(),
            'level' => $this->faker->randomElement(['Critical', 'High', 'Medium', 'Low']),
            'uid' => $this->faker->uuid(),
            'title' => $this->faker->sentence(),
        ];
    }

    public function assetMonitored(): self
    {
        return $this->state(function (array $attributes) {
            return [];
        })->afterCreating(function (Alert $alert) {
            $asset = $alert->asset();
            $asset->is_monitored = true;
            $asset->save();
        });
    }

    public function assetUnmonitored(): self
    {
        return $this->state(function (array $attributes) {
            return [];
        })->afterCreating(function (Alert $alert) {
            $asset = $alert->asset();
            $asset->is_monitored = false;
            $asset->save();
        });
    }

    public function levelCritical(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'Critical',
            ];
        });
    }

    public function levelHigh(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'High',
            ];
        });
    }

    public function levelMedium(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'Medium',
            ];
        });
    }

    public function levelLow(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'Low',
            ];
        });
    }
}
