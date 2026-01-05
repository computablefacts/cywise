<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\ThoughtActionObservation;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RemoteAction extends AbstractAction
{
    private \App\Models\RemoteAction $action;

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

        $examples = empty($this->action->examples) ? "" : "For example:\n-" . implode("\n-", $this->action->examples);

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
        /** @param ThoughtActionObservation[] $chainOfThought */
        $chainOfThought = [];
        $action = $this->action;

        // Extract parameters from input
        $params = json_decode($input, true);

        if ($params === null) {
            $chainOfThought[] = new ThoughtActionObservation("Extract parameters for the remote action {$this->name()} parameters.", "extract_parameters[{$input}]", "The extraction failed: {$input}");
            return new FailedAnswer("Parameter extraction failed for action {$this->name()}", $chainOfThought);
        }

        $chainOfThought[] = new ThoughtActionObservation("Extract parameters for the remote action {$this->name()} parameters.", "extract_parameters[{$input}]", "The extraction succeeded. I must now validate the parameters.");

        // Validate the parameters
        $validator = $this->buildValidator($action->schema ?? [], $params);

        if ($validator->fails()) {
            $chainOfThought[] = new ThoughtActionObservation("Validate the remote action {$this->name()} parameters.", "validate_parameters[" . json_encode($params) . "]", "The validation failed: {$validator->errors()->toJson()}");
            return new FailedAnswer("Parameter validation failed for action {$this->name()}", $chainOfThought);
        }

        $chainOfThought[] = new ThoughtActionObservation("Validate the remote action {$this->name()} parameters.", "validate_parameters[" . json_encode($params) . "]", "The validation succeeded. I must now build the payload.");

        // Build the JSON-RPC payload
        $payload = $this->buildPayload($action->payload_template, $params);
        $chainOfThought[] = new ThoughtActionObservation("Build the remote action {$this->name()} payload.", "build_payload[" . json_encode($params) . "]", "The payload has been built: " . json_encode($payload) . ". I must now call the endpoint.");

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
            $chainOfThought[] = new ThoughtActionObservation("Call the remote action {$this->name()} endpoint.", "call[{$action->url}]", "The endpoint has been called but the call failed: {$e->getMessage()}");
            return new FailedAnswer("Remote action call failed for action {$this->name()}", $chainOfThought);
        }
        if ($response->failed()) {
            $chainOfThought[] = new ThoughtActionObservation("Call the remote action {$this->name()} endpoint.", "call[{$action->url}]", "The endpoint has been called but the call failed: {$response->body()}");
            return new FailedAnswer("Remote action call failed for action {$this->name()}", $chainOfThought);
        }

        $data = $response->json();

        // Ensure the response is not a JSON-RPC error
        if (isset($data['error'])) {
            $chainOfThought[] = new ThoughtActionObservation("Call the remote action {$this->name()} endpoint.", "call[{$action->url}]", "The endpoint has been called but the call failed: " . json_encode($data));
            return new FailedAnswer("Remote action call failed for action {$this->name()}", $chainOfThought);
        }

        $chainOfThought[] = new ThoughtActionObservation("Call the remote action {$this->name()} endpoint.", "call[{$action->url}]", "The endpoint has been called and the call succeeded: " . json_encode($data));

        // Build the response
        if (empty($action->response_template)) {
            $transformation = $data;
            $chainOfThought[] = new ThoughtActionObservation("Return data from action {$this->name()} as-is.", "transform[" . json_encode($data) . "]", "The data have not been transformed: " . json_encode($transformation));
        } else {
            $transformation = $this->buildResponse($action->response_template, $payload['params'], $data);
            $chainOfThought[] = new ThoughtActionObservation("Return data from action {$this->name()} after transformation.", "transform[" . json_encode($data) . "]", "The data have been transformed: {$transformation}");
        }
        return new SuccessfulAnswer(
            is_string($transformation) ? $transformation : json_encode($transformation),
            $chainOfThought
        );
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
