<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\YnhServer>
 */
class YnhServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dateTime = $this->faker->dateTimeBetween('-24 hour', 'now');

        return [
            'created_by' => Auth::user()->id ?? User::factory(),
            'created_at' => $dateTime,
            'updated_at' => $dateTime,
            'name' => $this->faker->word,
            'ip_address' => $this->faker->ipv4,
            'is_ready' => true,
        ];
    }
}
