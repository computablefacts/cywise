<?php

namespace App\AgentSquad\Providers;

use App\Http\Procedures\PromptsProcedure;
use App\Http\Requests\JsonRpcRequest;
use Illuminate\Support\Str;

class PromptsProvider
{
    public static function provide(string $name, array $variables = []): string
    {
        $request = new JsonRpcRequest(['name' => $name]);
        $request->setUserResolver(fn() => auth()->user());
        $prompt = (new PromptsProcedure())->get($request)['prompt'];
        $prompt = $prompt ? $prompt->template : '';
        foreach ($variables as $key => $value) {
            $prompt = Str::replace('{' . $key . '}', $value, $prompt);
        }
        return $prompt;
    }
}