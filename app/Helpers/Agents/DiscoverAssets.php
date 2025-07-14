<?php

namespace App\Helpers\Agents;

use App\Http\Controllers\AssetController;
use App\Models\User;
use Illuminate\Http\Request;

/** @deprecated */
class DiscoverAssets extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "discover_assets",
                "description" => "Discover assets related to a domain.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "domain" => [
                            "type" => ["string"],
                            "description" => "A domain or subdomain.",
                        ],
                    ],
                    "required" => ["domain"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct(User $user, string $threadId, array $messages, array $args = [])
    {
        parent::__construct($user, $threadId, $messages, $args);
    }

    function execute(): AbstractAction
    {
        try {
            $domain = $this->args['domain'] ?? null;
            $request = new Request(['domain' => $domain]);
            $request->setUserResolver(fn() => auth()->user());
            $response = (new AssetController())->discover($request);
            $count = count($response['subdomains']);

            $this->output = [
                "message" => "{$count} subdomains discovered.",
                "assets" => collect($response['subdomains']),
            ];

        } catch (\Exception $e) {
            $this->output = [
                "message" => $e->getMessage(),
                "assets" => collect(),
            ];
        }
        return $this;
    }

    public function html(): string
    {
        $header = "<th>Asset</th>";
        $rows = $this->output['assets']->map(fn(string $domain) => "<tr><td>{$domain}</td></tr>")->join("\n");
        return self::htmlTable($header, $rows, 1);
    }

    public function text(): string
    {
        return "{$this->output['message']}\n\nThe assets are : {$this->output['assets']->join(", ")}.";
    }

    public function markdown(): string
    {
        return $this->text();
    }
}
