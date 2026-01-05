<?php

namespace Database\Factories;

use App\Models\HiddenAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

class HiddenAlertFactory extends Factory
{
    protected $model = HiddenAlert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => $this->faker->uuid,
            'type' => null,
            'title' => null,
            'created_by' => Auth::user()->id ?? User::factory(),
        ];
    }

    public function hideUid(string $uid): self
    {
        return $this->state(function (array $attributes) use ($uid) {
            return [
                'uid' => $uid,
            ];
        });
    }

    public function hideType(string $type): self
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'type' => $type,
            ];
        });
    }

    public function hideTitle(string $title): self
    {
        return $this->state(function (array $attributes) use ($title) {
            return [
                'title' => $title,
            ];
        });
    }
}
