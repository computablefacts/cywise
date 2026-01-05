<?php

namespace Database\Factories;

use App\Models\AssetTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetTagHash>
 */
class AssetTagHashFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hash' => Str::random(32),
            'tag' => AssetTag::factory()->create()->tag,
        ];
    }
}
