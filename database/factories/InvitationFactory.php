<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'token' => Str::random(32),
            'sent_by' => User::factory(),
            'received_by' => null,
            'expires_at' => now()->addDays(30),
            'accepted_at' => null,
        ];
    }
}
