<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->slug(3);

        return [
            'collection_id' => Collection::factory(),
            'name' => $name,
            'name_normalized' => $name,
            'extension' => $this->faker->randomElement(['txt', 'pdf', 'docx', 'csv']),
            'path' => "/tmp/{$name}",
            'size' => $this->faker->numberBetween(512, 10485760),
            'md5' => Str::random(32),
            'sha1' => Str::random(40),
            'mime_type' => 'text/plain',
            'secret' => Str::random(40),
            'is_deleted' => false,
            'is_embedded' => false,
            'created_by' => Auth::user()->id ?? User::factory(),
        ];
    }

    public function deleted(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_deleted' => true,
        ]);
    }

    public function embedded(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_embedded' => true,
        ]);
    }

    public function forCollection(Collection $collection): self
    {
        return $this->state(fn (array $attributes) => [
            'collection_id' => $collection->id,
        ]);
    }
}
