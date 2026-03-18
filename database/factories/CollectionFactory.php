<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->slug(3),
            'priority' => 0,
            'is_deleted' => false,
            'created_by' => Auth::user()->id ?? User::factory(),
        ];
    }

    public function deleted(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_deleted' => true,
        ]);
    }

    public function private(): self
    {
        return $this->state(fn (array $attributes) => [
            'name' => "privcol{$attributes['created_by']}",
        ]);
    }
}
