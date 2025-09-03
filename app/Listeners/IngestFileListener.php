<?php

namespace App\Listeners;

use App\Events\IngestFile;
use App\Helpers\ApiUtilsFacade as ApiUtils;
use App\Http\Controllers\CyberBuddyController;
use App\Models\Chunk;
use App\Models\Collection;
use App\Models\File;
use App\Models\Vector;
use App\Rules\IsValidCollectionName;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class IngestFileListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof IngestFile)) {
            throw new \Exception('Invalid event type!');
        }
        if (!IsValidCollectionName::test($event->collection)) {
            Log::error("Invalid collection name : {$event->collection}");
            return;
        }

        Auth::login($event->user); // otherwise the tenant will not be properly set

        try {
            /** @var Collection $collection */
            $collection = Collection::where('name', $event->collection)
                ->where('is_deleted', false)
                ->first();

            if (!$collection) {
                $collection = Collection::create(['name' => $event->collection]);
            }

            /** @var File $file */
            $file = File::find($event->fileId);

            if (!$file) {
                throw new \Exception("Invalid file id : {$event->fileId}");
            }

            // webm to mp3
            if ($file->mime_type === 'audio/webm') {

                $webmFileContent = file_get_contents($file->downloadUrl());

                if ($webmFileContent === false) {
                    throw new \Exception("Failed to download webm file : {$file->downloadUrl()}");
                }

                // Download webm file
                $webmFilePath = "/tmp/{$file->name_normalized}.{$file->extension}";
                file_put_contents($webmFilePath, $webmFileContent);

                // webm to mp3
                $mp3FilePath = "/tmp/{$file->name_normalized}.mp3";
                $process = Process::fromShellCommandline("ffmpeg -i " . escapeshellarg($webmFilePath) . " " . escapeshellarg($mp3FilePath));
                $process->run();

                // Cleanup
                unlink($webmFilePath);

                if (!$process->isSuccessful() || !file_exists($mp3FilePath)) {
                    throw new \Exception("Failed to convert webm to mp3 : {$file->downloadUrl()}");
                }

                // Upload file to S3
                $collection = $file->collection()->where('is_deleted', false)->first();

                if (!$collection) {
                    throw new \Exception("Unknown file collection : {$file->downloadUrl()}");
                }
                if (!Storage::disk('files-s3')->putFileAs($this->storageFilePath($collection), new \Illuminate\Http\File($mp3FilePath), $this->storageFileName($file, 'mp3'))) {
                    throw new \Exception("Failed to upload mp3 file : {$mp3FilePath}");
                }

                // Replace the webm reference by the mp3 one
                $file->extension = 'mp3';
                $file->mime_type = 'audio/mpeg';
                $file->save();

                // Cleanup
                unlink($mp3FilePath);
            }

            // Speech-to-text
            if ($file->mime_type === 'audio/mpeg' || $file->mime_type === 'audio/wav') {

                $response = ApiUtils::whisper($file->downloadUrl());

                if ($response['error']) {
                    throw new \Exception($response['error_details']);
                }

                // Write text to disk
                $txtFilePath = "/tmp/{$file->name_normalized}.txt";
                file_put_contents($txtFilePath, $response['text']);

                // Move file to storage
                $collection = $file->collection()->where('is_deleted', false)->first();

                if (!$collection) {
                    throw new \Exception("Unknown file collection : {$file->downloadUrl()}");
                }
                if (!Storage::disk('files-s3')->putFileAs($this->storageFilePath($collection), new \Illuminate\Http\File($txtFilePath), $this->storageFileName($file, 'txt'))) {
                    throw new \Exception("Failed to upload text file : {$txtFilePath}");
                }

                // Replace the mp3 reference by the txt one
                $file->extension = 'txt';
                $file->mime_type = 'text/plain';
                $file->save();

                // Cleanup
                unlink($txtFilePath);
            }
            if ($file->mime_type === 'application/json' || $file->mime_type === 'application/x-ndjason' /* jsonl */) {

                $jsonFileContent = file_get_contents($file->downloadUrl());

                if ($jsonFileContent === false) {
                    throw new \Exception("Failed to download json file : {$file->downloadUrl()}");
                }

                // Download json file
                $jsonFilePath = "/tmp/{$file->name_normalized}.{$file->extension}";
                file_put_contents($jsonFilePath, $jsonFileContent);

                // Stream json rows as a collection
                $jsonStream = fopen($jsonFilePath, 'r');

                if ($jsonStream === false) {
                    throw new \Exception("Failed to open json file for streaming : {$jsonFilePath}");
                }

                $files = [];

                while (($line = fgets($jsonStream)) !== false) {

                    $obj = json_decode(trim($line), true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error("JSON decoding error : " . json_last_error_msg());
                        continue;
                    }
                    if (!isset($obj['page']) || !isset($obj['text'])) {
                        if (isset($obj['file'])) { // {"file":"https://cyber.gouv.fr/sites/default/files/2017/01/guide_hygiene_informatique_anssi.pdf"}
                            CyberBuddyController::saveDistantFile($collection, $obj['file']);
                        } else {
                            Log::error("Invalid JSON format : " . $line);
                        }
                    } else if (isset($obj['file'])) { // {"file":"https://cyber.gouv.fr/sites/default/files/2017/01/guide_hygiene_informatique_anssi.pdf", "page": 1, "text": "My own text!"}

                        /** @var File $fileTmp */
                        $fileTmp = $files[$obj['file']] ?? null;

                        if (!$fileTmp) {
                            $url = CyberBuddyController::saveDistantFile($collection, $obj['file'], false);
                            /** @var File $fileTmp */
                            $fileTmp = File::where('secret', Str::afterLast($url, '/'))->first();
                            if ($fileTmp) {
                                $files[$obj['file']] = $fileTmp;
                            }
                        }
                        if (!$fileTmp) {
                            Log::error("File cannot be downloaded : " . $obj['file']);
                        } else {

                            /** @var Chunk $chunk */
                            $chunk = $collection->chunks()->create([
                                'file_id' => $fileTmp->id,
                                'url' => $fileTmp->downloadUrl(),
                                'page' => $obj['page'],
                                'text' => $obj['text'],
                                'is_embedded' => isset($obj['hypothetical_questions']),
                            ]);

                            if (isset($obj['tags'])) {
                                foreach ($obj['tags'] as $tag) {
                                    $chunk->tags()->create(['tag' => Str::lower($tag)]);
                                }
                            }
                            if ($chunk->is_embedded) {
                                foreach ($obj['hypothetical_questions'] as $question) {
                                    if (Vector::isSupportedByMariaDb()) {
                                        $isOk = Vector::insertVector(
                                            $chunk->collection_id,
                                            $fileTmp->id,
                                            $chunk->id,
                                            $question['language'],
                                            $question['question'],
                                            $question['embedding']
                                        );
                                    } else {
                                        /** @var Vector $vector */
                                        $vector = $chunk->vectors()->create([
                                            'collection_id' => $chunk->collection_id,
                                            'file_id' => $fileTmp->id,
                                            'locale' => $question['language'],
                                            'hypothetical_question' => $question['question'],
                                            'embedding' => $question['embedding'],
                                        ]);
                                    }
                                }
                            }
                        }
                    } else { // {"page":11,"tags":["titre","section","sous-section"], "text": "Bonjour monde."}
                        if (Str::length($obj['text']) < 5000 /* database column size */) {

                            /** @var Chunk $chunk */
                            $chunk = $collection->chunks()->create([
                                'file_id' => $file->id,
                                'url' => $file->downloadUrl(),
                                'page' => $obj['page'],
                                'text' => $obj['text'],
                                'is_embedded' => isset($obj['hypothetical_questions']),
                            ]);

                            if (isset($obj['tags'])) {
                                foreach ($obj['tags'] as $tag) {
                                    $chunk->tags()->create(['tag' => Str::lower($tag)]);
                                }
                            }
                            if ($chunk->is_embedded) {
                                foreach ($obj['hypothetical_questions'] as $question) {
                                    if (Vector::isSupportedByMariaDb()) {
                                        $isOk = Vector::insertVector(
                                            $chunk->collection_id,
                                            $file->id,
                                            $chunk->id,
                                            $question['language'],
                                            $question['question'],
                                            $question['embedding']
                                        );
                                    } else {
                                        /** @var Vector $vector */
                                        $vector = $chunk->vectors()->create([
                                            'collection_id' => $chunk->collection_id,
                                            'file_id' => $file->id,
                                            'locale' => $question['language'],
                                            'hypothetical_question' => $question['question'],
                                            'embedding' => $question['embedding'],
                                        ]);
                                    }
                                }
                            }
                        } else {

                            $parts = explode("\n\n", $obj['text']);
                            $currentChunk = '';

                            foreach ($parts as $part) {
                                if (Str::length($currentChunk . "\n\n" . $part) < 5000 /* database column size */) {
                                    $currentChunk .= ($currentChunk ? "\n\n" : '') . $part;
                                } else {
                                    if ($currentChunk) {

                                        /** @var Chunk $chunk */
                                        $chunk = $collection->chunks()->create([
                                            'file_id' => $file->id,
                                            'url' => $file->downloadUrl(),
                                            'page' => $obj['page'],
                                            'text' => $currentChunk,
                                            'is_embedded' => isset($obj['hypothetical_questions']),
                                        ]);

                                        if (isset($obj['tags'])) {
                                            foreach ($obj['tags'] as $tag) {
                                                $chunk->tags()->create(['tag' => Str::lower($tag)]);
                                            }
                                        }
                                        if ($chunk->is_embedded) {
                                            foreach ($obj['hypothetical_questions'] as $question) {
                                                if (Vector::isSupportedByMariaDb()) {
                                                    $isOk = Vector::insertVector(
                                                        $chunk->collection_id,
                                                        $file->id,
                                                        $chunk->id,
                                                        $question['language'],
                                                        $question['question'],
                                                        $question['embedding']
                                                    );
                                                } else {
                                                    /** @var Vector $vector */
                                                    $vector = $chunk->vectors()->create([
                                                        'collection_id' => $chunk->collection_id,
                                                        'file_id' => $file->id,
                                                        'locale' => $question['language'],
                                                        'hypothetical_question' => $question['question'],
                                                        'embedding' => $question['embedding'],
                                                    ]);
                                                }
                                            }
                                        }
                                    }
                                    $currentChunk = $part;
                                }
                            }
                            if ($currentChunk) {

                                /** @var Chunk $chunk */
                                $chunk = $collection->chunks()->create([
                                    'file_id' => $file->id,
                                    'url' => $file->downloadUrl(),
                                    'page' => $obj['page'],
                                    'text' => $currentChunk,
                                    'is_embedded' => isset($obj['hypothetical_questions']),
                                ]);

                                if (isset($obj['tags'])) {
                                    foreach ($obj['tags'] as $tag) {
                                        $chunk->tags()->create(['tag' => Str::lower($tag)]);
                                    }
                                }
                                if ($chunk->is_embedded) {
                                    foreach ($obj['hypothetical_questions'] as $question) {
                                        if (Vector::isSupportedByMariaDb()) {
                                            $isOk = Vector::insertVector(
                                                $chunk->collection_id,
                                                $file->id,
                                                $chunk->id,
                                                $question['language'],
                                                $question['question'],
                                                $question['embedding']
                                            );
                                        } else {
                                            /** @var Vector $vector */
                                            $vector = $chunk->vectors()->create([
                                                'collection_id' => $chunk->collection_id,
                                                'file_id' => $file->id,
                                                'locale' => $question['language'],
                                                'hypothetical_question' => $question['question'],
                                                'embedding' => $question['embedding'],
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Mark the whole file as 'embedded'
                if (!Chunk::where('file_id', $file->id)->where('is_embedded', false)->exists()) {
                    $file->is_embedded = true;
                    $file->save();
                }

                // Cleanup
                fclose($jsonStream);
                unlink($jsonFilePath);

            } else {

                $response = ApiUtils::file_input($event->user->client(), $file->downloadUrl(), "{$file->name}.{$file->extension}");

                if ($response['error']) {
                    throw new \Exception($response['error_details']);
                }

                $fragments = $response['response'];

                foreach ($fragments as $fragment) {

                    $tags = explode('>', $fragment['metadata']['title']);
                    $page = $fragment['metadata']['page_idx'] + 1;

                    if ($fragment['metadata']['tag'] === 'list') {
                        $text = trim($fragment['metadata']['prevPara']['text']) . "\n" . trim($fragment['text']);
                    } else {
                        $text = trim($fragment['text']);
                    }

                    /** @var Chunk $chunk */
                    $chunk = $collection->chunks()->create([
                        'file_id' => $file->id,
                        'url' => $file->downloadUrl(),
                        'page' => $page,
                        'text' => $text,
                    ]);

                    foreach ($tags as $tag) {
                        $chunk->tags()->create(['tag' => Str::lower($tag)]);
                    }
                }
            }
            if (!Chunk::where('file_id', $file->id)->exists()) { // no chunks -> no embeddings -> processing is complete
                $file->is_embedded = true;
                $file->save();
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    private function storageFileName(File $file, string $extension): string
    {
        return "{$file->id}_{$file->name_normalized}.{$extension}";
    }

    private function storageFilePath(\App\Models\Collection $collection): string
    {
        return "/cyber-buddy/{$collection->id}";
    }
}
