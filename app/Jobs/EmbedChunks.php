<?php

namespace App\Jobs;

use App\AgentSquad\Providers\HypotheticalQuestionsProvider;
use App\Models\Chunk;
use App\Models\Collection;
use App\Models\File;
use App\Models\Vector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EmbedChunks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 3 * 180; // 9mn

    public function __construct()
    {
        //
    }

    public function handle()
    {
        Collection::where('is_deleted', false)
            ->get()
            ->each(function (Collection $collection) {
                $collection->chunks()
                    ->where('is_embedded', false)
                    ->where('is_deleted', false)
                    ->chunk(500, function ($chunks) use ($collection) {

                        /** @var Chunk $chunk */
                        foreach ($chunks as $chunk) {

                            $lang = $chunk->language();
                            $questions = HypotheticalQuestionsProvider::provide($lang, $chunk->text);

                            foreach ($questions as $question) {
                                if (Vector::isSupportedByMariaDb()) {
                                    $isOk = Vector::insertVector(
                                        $chunk->collection_id,
                                        $chunk->file_id,
                                        $chunk->id,
                                        $question['language'],
                                        $question['question'],
                                        $question['embedding']
                                    );
                                } else {
                                    /** @var Vector $vector */
                                    $vector = $chunk->vectors()->create([
                                        'collection_id' => $chunk->collection_id,
                                        'file_id' => $chunk->file_id,
                                        'locale' => $question['language'],
                                        'hypothetical_question' => $question['question'],
                                        'embedding' => $question['embedding'],
                                    ]);
                                }
                            }

                            $chunk->is_embedded = true;
                            $chunk->save();

                            if (!Chunk::where('file_id', $chunk->file_id)->where('is_embedded', false)->exists()) {
                                File::where('id', $chunk->file_id)->update(['is_embedded' => true]);
                            }
                        }
                    });
            });
    }
}
