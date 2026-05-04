<?php

namespace App\Jobs;

use App\AgentSquad\Assistants\ChunkAssistant;
use App\Enums\LanguageEnum;
use App\Listeners\AbstractListener;
use App\Models\Chunk;
use App\Models\File;
use App\Models\User;
use App\Models\Vector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmbedChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const DISPATCH_TTL_IN_SECONDS = 20 * 60;
    public const LOCK_TTL_IN_SECONDS = 2 * 60;

    public int $tries = 1;
    public int $maxExceptions = 1;
    public int $timeout = 3 * 180; // 9mn

    public function __construct(
        public int $chunkId,
    ) {
    }

    public static function lockKey(int $chunkId): string
    {
        return "embed-chunk:{$chunkId}";
    }

    public static function dispatchKey(int $chunkId): string
    {
        return "embed-chunk-dispatched:{$chunkId}";
    }

    public function handle(): void
    {
        $lock = Cache::lock(self::lockKey($this->chunkId), self::LOCK_TTL_IN_SECONDS);

        if (!$lock->get()) {
            return;
        }

        try {
            /** @var ?Chunk $chunk */
            $chunk = Chunk::withoutGlobalScope('tenant_scope')->find($this->chunkId);

            if (!$chunk || $chunk->is_deleted || $chunk->is_embedded) {
                return;
            }

            /** @var ?User $user */
            $user = User::withoutGlobalScope('tenant_scope')->find($chunk->created_by);

            if (!$user) {
                Log::error("Chunk has no createdBy user : {$chunk->id}");
                return;
            }

            $user->actAs();

            File::withoutGlobalScope('tenant_scope')
                ->where('id', $chunk->file_id)
                ->update(['is_embedded' => false]);

            $lang = $chunk->language();
            $questions = ChunkAssistant::use()
                ->withLang(LanguageEnum::tryFrom($lang) ?? LanguageEnum::FRENCH)
                ->withChunk($chunk->text)
                ->hypotheticalQuestions();

            foreach ($questions as $question) {
                if (Vector::isSupportedByMariaDb()) {
                    Vector::insertVector(
                        $chunk->collection_id,
                        $chunk->file_id,
                        $chunk->id,
                        $question['language'],
                        $question['question'],
                        $question['embedding']
                    );
                } else {
                    $chunk->vectors()->create([
                        'collection_id' => $chunk->collection_id,
                        'file_id' => $chunk->file_id,
                        'locale' => $question['language'],
                        'hypothetical_question' => $question['question'],
                        'embedding' => $question['embedding'],
                        'created_by' => $chunk->created_by,
                    ]);
                }
            }

            $chunk->is_embedded = true;
            $chunk->save();

            if (!Chunk::withoutGlobalScope('tenant_scope')
                ->where('file_id', $chunk->file_id)
                ->where('is_deleted', false)
                ->where('is_embedded', false)
                ->exists()) {
                File::withoutGlobalScope('tenant_scope')
                    ->where('id', $chunk->file_id)
                    ->update(['is_embedded' => true]);
            }
        } finally {
            Cache::forget(self::dispatchKey($this->chunkId));
            $lock->release();
        }
    }
}
