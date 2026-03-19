<?php

namespace App\Jobs;

use App\Listeners\AbstractListener;
use App\Models\Chunk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class EmbedChunks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const DISPATCH_BATCH_SIZE = 60;

    public int $tries = 1;
    public int $maxExceptions = 1;
    public int $timeout = 60;

    public function handle(): void
    {
        Chunk::withoutGlobalScope('tenant_scope')
            ->where('is_embedded', false)
            ->where('is_deleted', false)
            ->orderBy('id')
            ->limit(self::DISPATCH_BATCH_SIZE)
            ->pluck('id')
            ->each(function (int $chunkId) {
                if (!Cache::add(EmbedChunk::dispatchKey($chunkId), true, now()->addSeconds(EmbedChunk::DISPATCH_TTL_IN_SECONDS))) {
                    return;
                }

                EmbedChunk::dispatch($chunkId)->onQueue(AbstractListener::LOW);
            });
    }
}
