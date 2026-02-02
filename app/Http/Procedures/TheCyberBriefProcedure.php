<?php

namespace App\Http\Procedures;

use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\WebpagesProvider;
use App\Http\Requests\JsonRpcRequest;
use Illuminate\Support\Str;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class TheCyberBriefProcedure extends Procedure
{
    public static string $name = 'the-cyber-brief';

    #[RpcMethod(
        description: "Summarize a text or a webpage.",
        params: [
            "url_or_text" => "The text or webpage (URL) to summarize. The webpage will be automatically downloaded and converted to text.",
            "prompt" => "The prompt to use. Any [TEXT] in the prompt will be replaced by the text or webpage.",
        ],
        result: [
            "summary" => "The summary of the text or webpage.",
        ]
    )]
    public function summarize(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'url_or_text' => 'required|string',
            'prompt' => 'required|string',
            'model' => 'nullable|string',
        ]);

        $text = $params['url_or_text'] ?? '';
        $prompt = $params['prompt'] ?? '';
        $model = $params['model'] ?? 'gpt-4o';
        $content = WebpagesProvider::isHyperlink($text) ? WebpagesProvider::provide($text) : $text;
        $response = LlmsProvider::provide(Str::replace('[TEXT]', $content, $prompt), $model);

        return [
            "summary" => $response,
        ];
    }
}