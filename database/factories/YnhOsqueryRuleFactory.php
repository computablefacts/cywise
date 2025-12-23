<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\YnhOsqueryRule>
 */
class YnhOsqueryRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => Auth::user()->id ?? User::factory(),
            'name' => function (array $attributes) {
                $createdByUser = User::find($attributes['created_by']);
                $name = $this->faker->unique()->regexify('[a-z]+[a-z0-9_]*[a-z0-9]+');

                return $createdByUser->isCywiseAdmin() ? $name : $createdByUser->tenant_id.'_cywise_'.$name;
            },
            'description' => $this->faker->sentence(10),
            'enabled' => $this->faker->boolean(80),
            'query' => 'SELECT * FROM '.$this->faker->word().';',
        ];
    }
}
