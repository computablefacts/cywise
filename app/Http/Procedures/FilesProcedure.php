<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use App\Models\File;
use App\Models\User;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class FilesProcedure extends Procedure
{
    public static string $name = 'files';

    #[RpcMethod(
        description: "Delete a single file.",
        params: [
            'file_id' => 'The file id.',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'file_id' => 'required|integer|exists:cb_files,id',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var File $file */
        $file = File::query()
            ->where('created_by', $user->id)
            ->where('id', '=', $params['file_id'])
            ->first();

        if (!isset($file)) {
            throw new \Exception("The file cannot be found.");
        }

        $file->is_deleted = true;
        $file->save();

        return [
            "msg" => "Your file will be deleted soon!"
        ];
    }

    #[RpcMethod(
        description: "List all files that belong to the current user.",
        params: [
            'page' => 'The page number (optional, default 1).',
            'page_size' => 'The page size (optional, default 25).',
            'collection' => 'The collection name (optional).',
        ],
        result: [
            "page" => "The current page number.",
            "page_size" => "The page size.",
            "nb_pages" => "The total number of pages.",
            "collection" => 'The collection name (if any).',
            "files" => "A list of files.",
        ]
    )]
    public function list(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'collection' => ['nullable', 'string', 'min:1', 'max:100'],
        ]);
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 25;
        $collection = $params['collection'] ?? null;

        /** @var User $user */
        $user = $request->user();
        $query = File::select('cb_files.*')
            ->join('cb_collections', 'cb_collections.id', '=', 'cb_files.collection_id')
            ->where('cb_files.created_by', $user->id)
            ->where('cb_files.is_deleted', false)
            ->where('cb_collections.is_deleted', false)
            ->where(function ($query) use ($user) {
                $query->where('cb_collections.name', "privcol{$user->id}")
                    ->orWhere('cb_collections.name', 'not like', "privcol%");
            })
            ->orderBy('cb_collections.name')
            ->orderBy('name_normalized')
            ->forPage($page <= 0 ? 1 : $page, $pageSize <= 0 ? 25 : $pageSize);

        if (!empty($collection)) {
            $query->where('cb_collections.name', $collection);
        }

        $nbPages = ceil($query->count() / $pageSize);
        $files = $query->get()
            ->map(function (File $file) {
                $nbChunks = $file->chunks()->count();
                $nbVectors = $file->chunks()->where('is_embedded', true)->count();
                $nbNotVectors = $file->chunks()->where('is_embedded', false)->count();
                return [
                    'id' => $file->id,
                    'name_normalized' => $file->name_normalized,
                    'collection' => $file->collection->name,
                    'filename' => "{$file->name_normalized}.{$file->extension}",
                    'created_at' => $file->created_at,
                    'created_by' => $file->createdBy,
                    'size' => $file->size,
                    'nb_chunks' => $nbChunks,
                    'nb_vectors' => $nbVectors,
                    'nb_not_vectors' => $nbNotVectors,
                    'status' => $file->is_embedded ? 'processed' : 'processing',
                    'download_url' => $file->downloadUrl(),
                ];
            });

        return [
            "page" => $page,
            "page_size" => $pageSize,
            "nb_pages" => $nbPages,
            "collection" => $collection,
            "files" => $files,
        ];
    }
}
