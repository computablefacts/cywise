<?php

namespace App\AgentSquad\Providers;

use App\Http\Procedures\PromptsProcedure;
use App\Http\Requests\JsonRpcRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PromptsProvider extends AbstractProvider
{
    private string $name;
    private array $variables = [];

    public static function use(): PromptsProvider
    {
        return new PromptsProvider();
    }

    public function withName(string $name): PromptsProvider
    {
        $this->name = $name;
        return $this;
    }

    public function withVariables(array $variables): PromptsProvider
    {
        $this->variables = $variables;
        return $this;
    }

    protected function provide2(): string
    {
        try {
            $request = new JsonRpcRequest(['name' => $this->name]);
            $request->setUserResolver(fn() => auth()->user());
            $prompt = (new PromptsProcedure())->get($request)['prompt'];
            $prompt = $prompt ? $prompt->template : '';
            foreach ($this->variables as $key => $value) {
                $prompt = Str::replace('{' . $key . '}', $value, $prompt);
            }
            return $prompt;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return '';
    }
}