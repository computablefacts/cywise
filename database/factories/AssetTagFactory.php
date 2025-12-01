<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

class AssetTagFactory extends Factory
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
            'tag' => $this->faker->word,
            'created_by' => Auth::user()->id ?? User::factory(),
        ];
    }
}
