<?php

namespace App\Http\Procedures;

use App\Helpers\LlmProvider;
use Illuminate\Http\Request;
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
    public function summarize(Request $request): array
    {
        $params = $request->validate([
            'url_or_text' => 'required|string',
            'prompt' => 'required|string',
            'model' => 'nullable|string',
        ]);

        $text = $params['url_or_text'] ?? '';
        $prompt = $params['prompt'] ?? '';
        $model = $params['model'] ?? 'gpt-4o';
        // $temperature = $request->float('temperature', 0.7);
        $content = LlmProvider::download($text);
        $response = (new LlmProvider(LlmProvider::OPEN_AI))->execute(Str::replace('[TEXT]', $content, $prompt), $model);

        if (isset($response['choices'][0]['message']['content'])) {
            return [
                "summary" => $response['choices'][0]['message']['content'],
            ];
        }
        throw new \Exception('An error occurred while summarizing the text or webpage.');
    }
}