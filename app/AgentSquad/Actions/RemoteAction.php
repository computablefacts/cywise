<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RemoteAction extends AbstractAction
{
    private \App\Models\RemoteAction $action;

    public function id(): ?int
    {
        return $this->action->id;
    }

    public function isRemote(): bool
    {
        return true;
    }

    protected function schema(): array
    {
        $parameters = "The following information are needed:\n";

        foreach ($this->action->schema as $key => $properties) {
            $parameters .= "- {$key}: {$properties['description']} ({$properties['type']})\n";
        }

        $examples = empty($this->action->examples) ? "" : "For example:\n- " . implode("\n- ", $this->action->examples);

        return [
            "type" => "function",
            "function" => [
                "name" => $this->action->name,
                "description" => "{$this->action->description}\n\n{$parameters}\n{$examples}",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "input" => [
                            "type" => "string",
                            "description" => "A JSON object with the parameters to be used in the remote action.",
                        ],
                    ],
                    "required" => ["input"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct(\App\Models\RemoteAction $action)
    {
        $this->action = $action;
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        $action = $this->action;

        // Extract parameters from input
        $params = json_decode($input, true);

        if ($params === null) {
            return new FailedAnswer("Parameter extraction failed for action {$this->name()}");
        }

        // Validate the parameters
        $validator = $this->buildValidator($action->schema ?? [], $params);

        if ($validator->fails()) {
            return new FailedAnswer("Parameter validation failed for action {$this->name()}");
        }

        // Build the JSON-RPC payload
        $payload = $this->buildPayload($action->payload_template, $params);

        // Call the JSON-RPC endpoint
        try {
            $url = Str::replace('{api_token}', $user->sentinelApiToken(), $action->url);
            if (!Str::endsWith($url, '/api/v2/private/endpoint')) {
                $headers = collect($action->headers)->toArray();
                $response = Http::withHeaders($headers)->timeout(5)->post($url, $payload);
            } else {
                $request = \Illuminate\Http\Request::create(
                    '/api/v2/private/endpoint',
                    'POST',
                    [], // paramètres query
                    [], // cookies
                    [], // fichiers
                    $_SERVER, // serveur
                    json_encode($payload)
                );
                $request->headers->set('Content-Type', 'application/json');
                $request->headers->set('Accept', 'application/json');
                $request->headers->set('Accept-Encoding', 'gzip');
                $request->setUserResolver(fn() => $user);
                $response = new class($request) {

                    private $raw;

                    public function __construct($request)
                    {
                        $this->raw = app()->handle($request);
                    }

                    public function __toString(): string
                    {
                        return $this->body();
                    }

                    public function failed(): bool
                    {
                        return !$this->raw->isSuccessful();
                    }

                    public function json(): array
                    {
                        return json_decode($this->body(), true);
                    }

                    public function body(): string
                    {
                        return gzdecode($this->raw->getContent()); // due to Sajya\Server\Middleware\GzipCompress
                    }
                };
            }
        } catch (\Exception $e) {
            return new FailedAnswer("Remote action call failed for action {$this->name()}");
        }
        if ($response->failed()) {
            return new FailedAnswer("Remote action call failed for action {$this->name()}");
        }

        $data = $response->json();

        // Ensure the response is not a JSON-RPC error
        if (isset($data['error'])) {
            return new SuccessfulAnswer($data['error']['message'] ?? "Remote action call failed for action {$this->name()}");
        }

        // Build the response
        if (empty($action->response_template)) {
            $transformation = $data;
        } else {
            $transformation = $this->buildResponse($action->response_template, $payload['params'], $data);
        }
        return new SuccessfulAnswer(is_string($transformation) ? $transformation : json_encode($transformation));
    }

    private function buildValidator(array $properties, array $params): \Illuminate\Validation\Validator
    {
        $rules = [];
        foreach ($properties as $key => $details) {
            if (isset($details['validation'])) {
                $rules[$key] = $details['validation'];
            }
        }
        return Validator::make($params, $rules);
    }

    /**
     * Recursively replace {{key}} dans un template (array ou string)
     */
    private function buildPayload(array|string $template, array $data): mixed
    {
        if (is_string($template)) {
            $placeholder = Str::between($template, '{{', '}}');
            if ($placeholder === $template) {
                return $template;
            }
            return Arr::get($data, $placeholder);
        }
        if (is_array($template)) {
            foreach ($template as $key => $value) {
                if (is_array($value) || is_string($value)) {
                    $template[$key] = $this->buildPayload($value, $data);
                }
            }
        }
        return $template;
    }

    private function buildResponse(string $template, array $params, array $data): string
    {
        return Blade::render($template, ['params' => $params, 'result' => $data['result']]);
    }
}
