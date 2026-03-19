<?php

namespace Tests\Feature;

use App\Jobs\EmbedChunk;
use App\Jobs\EmbedChunks;
use App\Models\Chunk;
use App\Models\Collection;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDb;

class EmbedChunksTest extends TestCaseWithDb
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'towerify.deepinfra.api' => 'https://deepinfra.test',
            'towerify.deepinfra.api_key' => 'test-key',
        ]);
    }

    public function test_dispatcher_enqueues_one_worker_job_per_pending_chunk(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $collection = Collection::factory()->create([
            'created_by' => $user->id,
        ]);
        $file = File::factory()->forCollection($collection)->create([
            'created_by' => $user->id,
        ]);

        Chunk::create([
            'collection_id' => $collection->id,
            'file_id' => $file->id,
            'text' => 'Chunk A',
            'is_embedded' => false,
            'is_deleted' => false,
            'created_by' => $user->id,
        ]);
        Chunk::create([
            'collection_id' => $collection->id,
            'file_id' => $file->id,
            'text' => 'Chunk B',
            'is_embedded' => false,
            'is_deleted' => false,
            'created_by' => $user->id,
        ]);
        Chunk::create([
            'collection_id' => $collection->id,
            'file_id' => $file->id,
            'text' => 'Chunk C',
            'is_embedded' => true,
            'is_deleted' => false,
            'created_by' => $user->id,
        ]);

        (new EmbedChunks())->handle();

        Bus::assertDispatchedTimes(EmbedChunk::class, 2);
    }

    public function test_dispatcher_skips_chunks_that_were_recently_dispatched(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $collection = Collection::factory()->create([
            'created_by' => $user->id,
        ]);
        $file = File::factory()->forCollection($collection)->create([
            'created_by' => $user->id,
        ]);
        $chunk = Chunk::create([
            'collection_id' => $collection->id,
            'file_id' => $file->id,
            'text' => 'Chunk A',
            'is_embedded' => false,
            'is_deleted' => false,
            'created_by' => $user->id,
        ]);

        Cache::put(EmbedChunk::dispatchKey($chunk->id), true, now()->addMinutes(5));

        (new EmbedChunks())->handle();

        Bus::assertNotDispatched(EmbedChunk::class);
    }

    public function test_worker_skips_chunk_when_cache_lock_is_already_held(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create([
            'created_by' => $user->id,
        ]);
        $file = File::factory()->forCollection($collection)->create([
            'created_by' => $user->id,
            'is_embedded' => false,
        ]);
        $chunk = Chunk::create([
            'collection_id' => $collection->id,
            'file_id' => $file->id,
            'text' => 'Locked chunk',
            'is_embedded' => false,
            'is_deleted' => false,
            'created_by' => $user->id,
        ]);
        Cache::put(EmbedChunk::dispatchKey($chunk->id), true, now()->addMinutes(5));

        $lock = Cache::lock(EmbedChunk::lockKey($chunk->id), EmbedChunk::LOCK_TTL_IN_SECONDS);

        $this->assertTrue($lock->get());

        try {
            (new EmbedChunk($chunk->id))->handle();
        } finally {
            $lock->release();
        }

        $chunk->refresh();

        $this->assertFalse($chunk->is_embedded);
        $this->assertDatabaseCount('cb_vectors', 0);
        $this->assertTrue(Cache::has(EmbedChunk::dispatchKey($chunk->id)));
    }

    public function test_worker_embeds_chunk_once_when_it_acquires_the_lock(): void
    {
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/chat/completions')) {
                return Http::response([
                    'choices' => [[
                        'message' => [
                            'content' => '',
                        ],
                    ]],
                ]);
            }

            return Http::response([], 404);
        });

        $user = User::factory()->create();
        $collection = Collection::factory()->create([
            'created_by' => $user->id,
            'name' => 'docs-lgfr',
        ]);
        $file = File::factory()->forCollection($collection)->create([
            'created_by' => $user->id,
            'is_embedded' => false,
        ]);
        $chunk = Chunk::create([
            'collection_id' => $collection->id,
            'file_id' => $file->id,
            'text' => 'Bonjour le monde',
            'is_embedded' => false,
            'is_deleted' => false,
            'created_by' => $user->id,
        ]);
        Cache::put(EmbedChunk::dispatchKey($chunk->id), true, now()->addMinutes(5));

        (new EmbedChunk($chunk->id))->handle();

        $chunk->refresh();
        $file->refresh();

        $this->assertTrue($chunk->is_embedded);
        $this->assertTrue($file->is_embedded);
        $this->assertDatabaseCount('cb_vectors', 0);
        $this->assertFalse(Cache::has(EmbedChunk::dispatchKey($chunk->id)));
    }
}
