<?php

namespace Database\Factories;

use App\Models\Chunk;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

class ChunkFactory extends Factory
{
    protected $model = Chunk::class;

    public function definition(): array
    {
        $file = File::factory();

        return [
            'collection_id' => $file,
            'file_id' => $file,
            'url' => $this->faker->optional()->url(),
            'page' => $this->faker->numberBetween(1, 10),
            'text' => $this->faker->text(200),
            'is_embedded' => false,
            'is_deleted' => false,
            'created_by' => Auth::user()->id ?? User::factory(),
        ];
    }

    public function deleted(): self
    {
        return $this->state(fn () => [
            'is_deleted' => true,
        ]);
    }

    public function embedded(): self
    {
        return $this->state(fn () => [
            'is_embedded' => true,
        ]);
    }

    public function forFile(File $file): self
    {
        return $this->state(fn () => [
            'collection_id' => $file->collection_id,
            'file_id' => $file->id,
        ]);
    }
}
