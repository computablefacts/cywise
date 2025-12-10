<?php

namespace App\Http\Procedures;

use App\Http\Controllers\CyberBuddyController;
use App\Http\Requests\JsonRpcRequest;
use App\Models\File;
use App\Models\YnhFramework;
use App\Rules\IsValidCollectionName;
use Illuminate\Support\Str;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class FrameworksProcedure extends Procedure
{
    public static string $name = 'frameworks';

    #[RpcMethod(
        description: "Load a framework into a collection.",
        params: [
            "framework_id" => "The framework id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function load(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'framework_id' => 'required|integer|exists:ynh_frameworks,id',
        ]);

        /** @var YnhFramework $framework */
        $framework = YnhFramework::findOrFail('id', $params['framework_id']);

        /** @var \App\Models\Collection $collection */
        $collection = \App\Models\Collection::where('name', $framework->collectionName())
            ->where('is_deleted', false)
            ->first();

        if (!$collection) {
            if (!IsValidCollectionName::test($framework->collectionName())) {
                throw new \Exception('Invalid collection name.');
            }
            $collection = \App\Models\Collection::create(['name' => $framework->collectionName()]);
        }

        $path = Str::replace('.jsonl.gz', '.2.jsonl.gz', $framework->path());
        $url = CyberBuddyController::saveLocalFile($collection, $path);

        if (!$url) {
            throw new \Exception('The framework could not be loaded.');
        }
        return [
            'msg' => 'The framework has been loaded and will be processed soon.',
        ];
    }

    #[RpcMethod(
        description: "Remove a framework from a collection.",
        params: [
            "framework_id" => "The framework id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function unload(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'framework_id' => 'required|integer|exists:ynh_frameworks,id',
        ]);
        /** @var YnhFramework $framework */
        $framework = YnhFramework::findOrFail($params['framework_id']);
        if ($framework->collection()) {
            File::where('is_deleted', false)
                ->where('collection_id', $framework->collection()->id)
                ->where('name', Str::trim(basename($framework->file, '.jsonl')))
                ->where('extension', 'jsonl')
                ->update(['is_deleted' => true]);
        }
        return [
            'msg' => 'The framework has been unloaded and will be removed soon.',
        ];
    }
}