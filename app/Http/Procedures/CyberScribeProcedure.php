<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class CyberScribeProcedure extends Procedure
{
    public static string $name = 'cyberscribe';

    #[RpcMethod(
        description: "List available templates.",
        params: [],
        result: [
            "templates" => "A list of templates.",
        ]
    )]
    public function listTemplates(JsonRpcRequest $request): array
    {
        return [
            'templates' => Template::where('readonly', true)
                ->orderBy('name', 'asc')
                ->get()
                ->concat(
                    Template::where('readonly', false)
                        ->where('created_by', $request->user()->id)
                        ->orderBy('name', 'asc')
                        ->get()
                )
                ->map(function (Template $template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'template' => $template->template,
                        'type' => $template->readonly ? 'template' : 'draft',
                        'user' => User::find($template->created_by)->name,
                    ];
                }),
        ];
    }

    #[RpcMethod(
        description: "Delete an existing template.",
        params: [
            "template_id" => "The template id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function deleteTemplate(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'template_id' => 'required|integer|exists:cb_templates,id',
        ]);
        Template::where('id', $params['template_id'])->where('readonly', false)->delete();
        return [
            'msg' => __('The template has been deleted!'),
        ];
    }

    #[RpcMethod(
        description: "Save a template.",
        params: [
            "template_id" => "The template id (optional).",
            "is_model" => "Whether the template is a model (optional).",
            "name" => "The template name.",
            "blocks" => "The template blocks.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function saveTemplate(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'template_id' => 'nullable|integer|exists:cb_templates,id',
            'is_model' => 'boolean',
            'name' => 'required|string|min:1|max:191',
            'blocks' => 'required|array',
        ]);
        $id = $params['template_id'] ?? 0;
        $name = $params['name'] ?? '';
        $blocks = $params['blocks'] ?? [];
        $isModel = $params['is_model'] ?? false;

        if (isset($blocks) && count($blocks) > 0) {
            if ($id === 0) {
                $template = Template::create([
                    'name' => Str::replace('v', '', $name),
                    'template' => $blocks,
                    'readonly' => $isModel,
                ]);
            } else {
                $template = Template::where('id', $id)->where('readonly', false)->first();
                $version = ($template && Str::contains($template->name, 'v') ? (int)Str::afterLast($template->name, 'v') : 0) + 1;
                if ($template) {
                    $template->name = Str::beforeLast($template->name, 'v') . "v{$version}";
                    $template->template = $blocks;
                    $template->save();
                } else {
                    $userId = Auth::user()->id;
                    $template = Template::create([
                        'name' => "{$name} u{$userId}v1",
                        'template' => $blocks,
                        'readonly' => false,
                    ]);
                }
            }
            return [
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'template' => $template->template,
                    'type' => $template->readonly ? 'template' : 'draft',
                    'user' => User::find($template->created_by)->name,
                ],
            ];
        }
        return [
            'template' => [],
        ];
    }
}