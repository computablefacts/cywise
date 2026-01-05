<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Scan;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Scan $scan) {
            if ($scan->ports_scan_id) {
                $asset = $scan->asset;
                $asset->next_scan_id = $scan->ports_scan_id;
                if ($scan->vulns_scan_ends_at) {
                    $asset->cur_scan_id = $scan->ports_scan_id;
                    $asset->next_scan_id = null;
                    $asset->save();
                }
            }
        });
    }
    
    public function portsScanStarted(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'ports_scan_id' => $this->faker->uuid(),
                'ports_scan_begins_at' => $this->faker->dateTimeBetween('-1 hours', 'now'),
            ];
        });
    }

    public function portsScanEnded(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'ports_scan_id' => $this->faker->uuid(),
                'ports_scan_begins_at' => $this->faker->dateTimeBetween('-2 hours', '-1 hours'),
                'ports_scan_ends_at' => $this->faker->dateTimeBetween('-1 hours', 'now'),
            ];
        });
    }

    public function vulnsScanStarted(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'ports_scan_id' => $this->faker->uuid(),
                'vulns_scan_id' => $this->faker->uuid(),
                'ports_scan_begins_at' => $this->faker->dateTimeBetween('-3 hours', '-2 hours'),
                'ports_scan_ends_at' => $this->faker->dateTimeBetween('-2 hours', '-1 hours'),
                'vulns_scan_begins_at' => $this->faker->dateTimeBetween('-1 hours', 'now'),
            ];
        });
    }

    public function vulnsScanEnded(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'ports_scan_id' => $this->faker->uuid(),
                'vulns_scan_id' => $this->faker->uuid(),
                'ports_scan_begins_at' => $this->faker->dateTimeBetween('-4 hours', '-3 hours'),
                'ports_scan_ends_at' => $this->faker->dateTimeBetween('-3 hours', '-2 hours'),
                'vulns_scan_begins_at' => $this->faker->dateTimeBetween('-2 hours', '-1 hours'),
                'vulns_scan_ends_at' => $this->faker->dateTimeBetween('-1 hours', 'now'),
            ];
        });
    }
}