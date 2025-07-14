<?php

namespace App\Helpers\Agents;

use App\Models\Asset;
use App\Models\Port;
use App\Models\User;

/** @deprecated */
class ListOpenPorts extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "list_open_ports",
                "description" => "Find the user's assets with open ports.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "asset" => [
                            "type" => ["string", "null"],
                            "description" => "An IP address, a domain or a subdomain.",
                        ],
                        "port" => [
                            "type" => ["integer", "null"],
                            "description" => "The port number.",
                        ],
                        "service" => [
                            "type" => ["string", "null"],
                            "description" => "The service bind to the port.",
                        ],
                        "technologies" => [
                            "type" => ["array", "null"],
                            "description" => "One or more technology tags associated to the port.",
                        ],
                    ],
                    "required" => [],
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
        $asset = $this->args['asset'] ?? null;
        if ($asset === 'null') {
            $asset = null;
        }
        $port = $this->args['port'] ?? null;
        if ($port === 'null') {
            $port = null;
        }
        $service = $this->args['service'] ?? null;
        if ($service === 'null') {
            $service = null;
        }
        $tags = $this->args['technologies'] ?? null;
        if ($tags === 'null') {
            $tags = null;
        }

        if (empty($asset)) {
            $assets = Asset::all();
        } else {
            /** @var Asset $asset */
            $asset = Asset::where('asset', $asset)->firstOrFail();
            $assets = collect([$asset]);
        }

        if (isset($tags) && is_string($tags)) {
            $tags = [$tags];
        }

        $this->output = $assets->flatMap(function (Asset $asset) use ($port, $service, $tags) {

            $query = $asset->ports()
                ->where('closed', false)
                ->orderBy('port');

            if (!empty($port)) {
                $query->where('port', $port);
            }
            if (!empty($service)) {
                $query->where('service', $service);
            }
            return $query->get()->filter(fn(Port $port) => !isset($tags) ||
                count($tags) <= 0 ||
                $port->tags()->whereIn('tag', $tags)->exists()
            );
        });
        return $this;
    }

    public function memoize(): bool
    {
        return false;
    }

    public function html(): string
    {
        $header = "
            <th>Asset</th>
            <th>IP</th>
            <th class='right'>Port</th>
            <th>Protocol</th>
            <th>Service</th>
            <th>Technologies</th>
        ";

        $rows = $this->output
            ->map(function (Port $port) {

                $tags = $port->tags()
                    ->orderBy('tag')
                    ->get()
                    ->pluck('tag')
                    ->map(fn(string $tag) => "<span class='lozenge new'>{$tag}</span>")
                    ->join(" ");

                return "
                    <tr>
                      <td>{$port->hostname}</td>
                      <td>{$port->ip}</td>
                      <td class='right'>{$port->port}</td>
                      <td>{$port->protocol}</td>
                      <td>{$port->service}</td>
                      <td>{$tags}</td>
                    </tr>
                ";
            })
            ->join("\n");

        return AbstractAction::htmlTable($header, $rows, 6);
    }

    public function text(): string
    {
        return $this->markdown();
    }

    public function markdown(): string
    {
        return $this->output->isEmpty() ? 'No open ports were found.'
            : $this->output->map(function (Port $port) {

                $tags = $port->tags()
                    ->orderBy('tag')
                    ->get()
                    ->pluck('tag')
                    ->join(", ");

                return "| {$port->hostname} | {$port->ip} | {$port->port} | {$port->protocol} | {$port->service} | {$tags} |";
            })
                ->prepend("| Asset | IP | Port | Protocol | Service | Technologies |")
                ->prepend("|---|---|---|---|---|---|")
                ->join("\n");
    }
}
