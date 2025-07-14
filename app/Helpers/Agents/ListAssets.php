<?php

namespace App\Helpers\Agents;

use App\Models\Asset;
use App\Models\User;

/** @deprecated */
class ListAssets extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "list_assets",
                "description" => "Find the user's assets.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "is_vulnerable" => [
                            "type" => ["boolean", "null"],
                            "description" => "True if the asset must have at least one vulnerability. False if it must have none. Null to list all assets.",
                        ],
                        "scan_in_progress" => [
                            "type" => ["boolean", "null"],
                            "description" => "True if the asset is being scanned. False if the scan has completed. Null to list all assets.",
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
        $isVulnerable = $this->args['is_vulnerable'] ?? null;
        if ($isVulnerable === 'null') {
            $isVulnerable = null;
        }
        $scanInProgress = $this->args['scan_in_progress'] ?? null;
        if ($scanInProgress === 'null') {
            $scanInProgress = null;
        }
        $this->output = Asset::all()
            ->sortBy('asset')
            ->filter(fn(Asset $asset) => !isset($isVulnerable) ||
                !is_bool($isVulnerable) ||
                ($isVulnerable && $asset->alerts()->count() > 0) ||
                (!$isVulnerable && $asset->alerts()->count() <= 0)
            )
            ->filter(fn(Asset $asset) => !isset($isVulnerable) ||
                !is_bool($scanInProgress) ||
                ($scanInProgress && $asset->scanInProgress()->isNotEmpty()) ||
                (!$scanInProgress && $asset->scanInProgress()->isEmpty())
            );
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
            <th class='right'>Nb. Open Ports</th>
            <th class='right'>Nb. Vulnerabilities</th>
            <th>Monitored?</th>
            <th>Scan in progress?</th>
            <th>Tags</th>
        ";

        $rows = $this->output
            ->map(function (Asset $asset) {

                if ($asset->is_monitored) {
                    if ($asset->scanInProgress()->isEmpty()) {
                        $scanInProgress = "<span class='lozenge success'>completed</span>";
                    } else {
                        $scanInProgress = "<span class='lozenge error'>running</span>";
                    }
                    $monitored = "<span class='lozenge success'>yes</span>";
                } else {
                    $scanInProgress = "n/a";
                    $monitored = "<span class='lozenge error'>no</span>";
                }

                $tags = $asset->tags()
                    ->orderBy('tag')
                    ->get()
                    ->pluck('tag')
                    ->map(fn(string $tag) => "<span class='lozenge new'>{$tag}</span>")
                    ->join(" ");

                return "
                    <tr>
                        <td>{$asset->asset}</td>
                        <td class='right'>{$asset->ports()->count()}</td>
                        <td class='right'>{$asset->alerts()->count()}</td>
                        <td>{$monitored}</td>
                        <td>{$scanInProgress}</td>
                        <td>{$tags}</td>
                    </tr>
                ";
            })
            ->join("\n");

        return self::htmlTable($header, $rows, 6);
    }

    public function text(): string
    {
        return $this->markdown();
    }

    public function markdown(): string
    {
        return $this->output->isEmpty() ? 'No assets to monitor. Please add one.'
            : $this->output->map(function (Asset $asset) {

                if ($asset->is_monitored) {
                    if ($asset->scanInProgress()->isEmpty()) {
                        $scanInProgress = "completed";
                    } else {
                        $scanInProgress = "running";
                    }
                    $monitored = "✅ Monitored";
                } else {
                    $scanInProgress = "n/a";
                    $monitored = "❌ Not Monitored";
                }

                $tags = $asset->tags()
                    ->orderBy('tag')
                    ->get()
                    ->pluck('tag')
                    ->join(",");

                return "| {$asset->asset} | {$asset->ports()->count()} | {$asset->alerts()->count()} | {$monitored} | {$scanInProgress} | {$tags} |";
            })
                ->prepend("| Asset | Number of Open Ports | Number of Vulnerabilities | Monitored? | Scan in progress? | Tags |")
                ->prepend("|---|---|---|---|---|---|")
                ->join("\n");
    }
}
