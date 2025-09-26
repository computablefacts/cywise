<?php

namespace App\Http\Procedures;

use App\Helpers\ApiUtilsFacade as ApiUtils;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Chunk;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class ChunksProcedure extends Procedure
{
    public static string $name = 'chunks';

    #[RpcMethod(
        description: "Delete a single chunk.",
        params: [
            'chunk_id' => 'The chunk id.',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'chunk_id' => 'required|integer|exists:cb_chunks,id',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var Chunk $chunk */
        $chunk = Chunk::query()
            ->where('created_by', $user->id)
            ->where('id', '=', $params['chunk_id'])
            ->first();

        if (!isset($chunk)) {
            throw new \Exception("The chunk cannot be found.");
        }

        $chunk->is_deleted = true;
        $chunk->save();

        return [
            "msg" => "Your chunk will be deleted soon!"
        ];
    }

    #[RpcMethod(
        description: "Update an existing chunk.",
        params: [
            'chunk_id' => 'The chunk id.',
            'value' => 'The new chunk value.',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function update(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'chunk_id' => 'required|integer|exists:cb_chunks,id',
            'value' => 'required|string|min:0|max:5000',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var Chunk $chunk */
        $chunk = Chunk::query()
            ->where('created_by', $user->id)
            ->where('id', '=', $params['chunk_id'])
            ->first();

        if (!isset($chunk)) {
            throw new \Exception("The chunk cannot be found.");
        }

        $chunk->text = $params['value'];
        $chunk->save();

        $response = ApiUtils::delete_chunks([$chunk->is_deleted], $chunk->collection->name);

        if ($response['error']) {
            Log::error($response['error_details']);
            throw new \Exception('The chunk has been saved but the embeddings could not be deleted.');
        }

        $chunk->is_embedded = false;
        $chunk->save();

        $response = ApiUtils::import_chunks([[
            'uid' => (string)$chunk->id,
            'text' => $chunk->text,
            'tags' => $chunk->tags()->pluck('tag')->toArray(),
        ]], $chunk->collection->name);

        if ($response['error']) {
            Log::error($response['error_details']);
            throw new \Exception('The chunk has been saved but the embeddings could not be updated.');
        }

        $chunk->is_embedded = true;
        $chunk->save();

        return [
            "msg" => "Your chunk will be updated soon!"
        ];
    }

    #[RpcMethod(
        description: "List all chunks that belong to the current user.",
        params: [
            'page' => 'The page number (optional, default 1).',
            'page_size' => 'The page size (optional, default 25).',
            'collection' => 'The collection name (optional).',
            'file' => 'The file name (optional).',
        ],
        result: [
            "page" => "The current page number.",
            "page_size" => "The page size.",
            "nb_pages" => "The total number of pages.",
            "collection" => 'The collection name (if any).',
            "file" => 'The file name (if any).',
            "chunks" => "A list of chunks.",
        ]
    )]
    public function list(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'collection' => ['nullable', 'string', 'min:1', 'max:100'],
            'file' => ['nullable', 'string', 'min:1', 'max:100'],
        ]);
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 25;
        $collection = $params['collection'] ?? null;
        $file = $params['file'] ?? null;

        /** @var User $user */
        $user = $request->user();
        $query = \App\Models\Chunk::select('cb_chunks.*')
            ->join('cb_collections', 'cb_collections.id', 'cb_chunks.collection_id')
            ->join('cb_files', 'cb_files.id', 'cb_chunks.file_id')
            ->where('cb_chunks.created_by', $user->id)
            ->where('cb_chunks.is_deleted', false)
            ->where('cb_collections.is_deleted', false)
            ->where(function ($query) use ($user) {
                $query->where('cb_collections.name', "privcol{$user->id}")
                    ->orWhere('cb_collections.name', 'not like', "privcol%");
            })
            ->orderBy('cb_collections.name')
            ->orderBy('cb_files.name')
            ->orderBy('cb_chunks.page')
            ->orderBy('cb_chunks.id')
            ->forPage($page <= 0 ? 1 : $page, $pageSize <= 0 ? 25 : $pageSize);

        if (!empty($collection)) {
            $query->where('cb_collections.name', $collection);
        }
        if (!empty($file)) {
            $query->where('cb_files.name_normalized', $file);
        }

        $nbPages = ceil($query->count() / $pageSize);
        $chunks = $query->get();

        return [
            "page" => $page,
            "page_size" => $pageSize,
            "nb_pages" => $nbPages,
            "collection" => $collection,
            "file" => $file,
            "chunks" => $chunks,
        ];
    }
}
