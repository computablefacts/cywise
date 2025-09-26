<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use App\Models\Prompt;
use App\Models\User;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class PromptsProcedure extends Procedure
{
    public static string $name = 'prompts';

    #[RpcMethod(
        description: "Get a given prompt.",
        params: [
            'name' => 'The prompt name.',
        ],
        result: [
            "prompt" => "A prompt object.",
        ]
    )]
    public function get(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'name' => 'required|string|min:1|max:191|exists:cb_prompts,name',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var Prompt $prompt */
        $prompt = Prompt::query()
            ->where('created_by', $user->id)
            ->where('name', '=', $params['name'])
            ->first();

        if (!isset($prompt)) {
            throw new \Exception("A prompt with the name '{$params['name']}' cannot be found.");
        }
        return [
            'prompt' => $prompt,
        ];
    }

    #[RpcMethod(
        description: "Create a new prompt.",
        params: [
            'name' => 'The prompt name.',
            'template' => 'The prompt template.',
        ],
        result: [
            "prompt" => "A prompt object.",
        ]
    )]
    public function create(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'name' => 'required|string|min:1|max:191',
            'template' => 'required|string|min:1|max:10000',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var Prompt $prompt */
        $prompt = Prompt::query()
            ->where('created_by', $user->id)
            ->where('name', '=', $params['name'])
            ->first();

        if (isset($prompt)) {
            throw new \Exception("A prompt with the name '{$params['name']}' already exists.");
        }

        /** @var Prompt $prompt */
        $prompt = Prompt::create([
            'created_by' => $user->id,
            'name' => $params['name'],
            'template' => $params['template']
        ]);

        return [
            "prompt" => $prompt,
        ];
    }

    #[RpcMethod(
        description: "Update an existing prompt.",
        params: [
            'prompt_id' => 'The prompt id.',
            'template' => 'The new prompt template.',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function update(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'prompt_id' => 'required|integer|exists:cb_prompts,id',
            'template' => 'required|string|min:1|max:10000',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var Prompt $prompt */
        $prompt = Prompt::query()
            ->where('created_by', $user->id)
            ->where('id', '=', $params['prompt_id'])
            ->first();

        if (!isset($prompt)) {
            throw new \Exception("The prompt cannot be found.");
        }

        $prompt->template = $params['template'];
        $prompt->save();

        return [
            "msg" => "Your prompt will be updated soon!"
        ];
    }

    #[RpcMethod(
        description: "Delete a single prompt.",
        params: [
            'prompt_id' => 'The prompt id.',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function delete(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'prompt_id' => 'required|integer|exists:cb_prompts,id',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var Prompt $prompt */
        $prompt = Prompt::query()
            ->where('created_by', $user->id)
            ->where('id', '=', $params['prompt_id'])
            ->first();

        if (!isset($prompt)) {
            throw new \Exception("The prompt cannot be found.");
        }

        $prompt->delete();

        return [
            "msg" => "Your prompt will be deleted soon!"
        ];
    }

    #[RpcMethod(
        description: "List all prompts that belong to the current user.",
        params: [
            'page' => 'The page number (optional, default 1).',
            'page_size' => 'The page size (optional, default 25).',
        ],
        result: [
            "page" => "The current page number.",
            "page_size" => "The page size.",
            "nb_pages" => "The total number of pages.",
            "prompts" => "A list of prompts.",
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
        $nbPages = ceil(Prompt::query()->where('created_by', $user->id)->count() / $pageSize);
        $prompts = Prompt::query()
            ->where('created_by', $user->id)
            ->orderBy('name')
            ->forPage($page <= 0 ? 1 : $page, $pageSize <= 0 ? 25 : $pageSize)
            ->get();

        return [
            "page" => $page,
            "page_size" => $pageSize,
            "nb_pages" => $nbPages,
            "prompts" => $prompts,
        ];
    }
}
