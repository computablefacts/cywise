<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        static $password;

        Role::createRoles();

        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $password ?: $password = bcrypt('secret'),
            'remember_token' => Str::random(60),
        ];
    }

    public function admin(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $attributes['name'].' (Admin)',
            ];
        })->afterCreating(function (User $user) {
            $user->assignRole(Role::ADMIN);
        });
    }
}
