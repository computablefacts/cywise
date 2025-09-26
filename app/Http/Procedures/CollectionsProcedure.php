<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use App\Models\Collection;
use App\Models\User;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class CollectionsProcedure extends Procedure
{
    public static string $name = 'collections';

    #[RpcMethod(
        description: "Update an existing collection.",
        params: [
            'collection_id' => 'The collection id.',
            'priority' => 'The new collection priority.',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function update(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'collection_id' => 'required|integer|exists:cb_prompts,id',
            'priority' => 'required|integer|min:0',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var Collection $collection */
        $collection = Collection::query()
            ->where('created_by', $user->id)
            ->where('id', '=', $params['collection_id'])
            ->first();

        if (!isset($collection)) {
            throw new \Exception("The collection cannot be found.");
        }

        $collection->priority = $params['priority'];
        $collection->save();

        return [
            "msg" => "Your collection will be updated soon!"
        ];
    }

    #[RpcMethod(
        description: "Delete a single collection.",
        params: [
            'collection_id' => 'The collection id.',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'collection_id' => 'required|integer|exists:cb_collections,id',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var Collection $collection */
        $collection = Collection::query()
            ->where('created_by', $user->id)
            ->where('id', '=', $params['collection_id'])
            ->first();

        if (!isset($collection)) {
            throw new \Exception("The collection cannot be found.");
        }

        $collection->is_deleted = true;
        $collection->save();

        return [
            "msg" => "Your collection will be deleted soon!"
        ];
    }

    #[RpcMethod(
        description: "List all collections that belong to the current user.",
        params: [
            'page' => 'The page number (optional, default 1).',
            'page_size' => 'The page size (optional, default 25).',
        ],
        result: [
            "page" => "The current page number.",
            "page_size" => "The page size.",
            "nb_pages" => "The total number of pages.",
            "collections" => "A list of collections.",
        ]
    )]
    public function list(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 25;

        /** @var User $user */
        $user = $request->user();
        $nbPages = ceil(Collection::query()->where('created_by', $user->id)->count() / $pageSize);
        $collections = \App\Models\Collection::query()
            ->where('created_by', $user->id)
            ->where('is_deleted', false)
            ->where(function ($query) use ($user) {
                $query->where('name', "privcol{$user->id}")
                    ->orWhere('name', 'not like', "privcol%");
            })
            ->orderBy('priority')
            ->orderBy('name')
            ->forPage($page <= 0 ? 1 : $page, $pageSize <= 0 ? 25 : $pageSize)
            ->get();

        return [
            "page" => $page,
            "page_size" => $pageSize,
            "nb_pages" => $nbPages,
            "collections" => $collections,
        ];
    }
}
