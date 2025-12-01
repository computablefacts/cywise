<?php

namespace Database\Factories;

use App\Models\Scan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PortFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scan_id' => Scan::factory(),
            'hostname' => $this->faker->domainName(),
            'ip' => $this->faker->ipv4(),
            'port' => $this->faker->numberBetween(1, 65535),
            'protocol' => $this->faker->randomElement(['tcp', 'udp']),
        ];
    }

    public function http(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'port' => 80,
                'protocol' => 'tcp',
                'ssl' => false,
            ];
        });
    }

    public function https(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'port' => 443,
                'protocol' => 'tcp',
                'ssl' => true,
            ];
        });
    }
}
