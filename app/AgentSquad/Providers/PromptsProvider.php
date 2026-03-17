<?php

namespace App\AgentSquad\Providers;

use App\Http\Procedures\PromptsProcedure;
use App\Http\Requests\JsonRpcRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** @deprecated */
class PromptsProvider extends AbstractProvider
{
    public static function provide(string $name, array $variables = []): string
    {
        $before = microtime(true);

        try {
            $request = new JsonRpcRequest(['name' => $name]);
            $request->setUserResolver(fn() => auth()->user());
            $prompt = (new PromptsProcedure())->get($request)['prompt'];
            $prompt = $prompt ? $prompt->template : '';
            foreach ($variables as $key => $value) {
                $prompt = Str::replace('{' . $key . '}', $value, $prompt);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $prompt = null;
        }

        $after = microtime(true);

        if (isset($prompt)) {
            self::traceSuccess('prompts', $before, $after);
            return $prompt;
        }

        self::traceError('prompts', $before, $after);
        return '';
    }
}