<?php

namespace App\Helpers;

use App\AgentSquad\SqlAssistant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ClickhouseLocal
{
    private function __construct()
    {
        //
    }

    public static function executeQuery(string $query): ?string
    {
        $process = Process::fromShellCommandline("clickhouse-local --query \"{$query}\"");
        $process->setTimeout(null);
        $process->run();

        if ($process->isSuccessful()) {
            $output = trim($process->getOutput());
            return empty($output) ? 'ok' : $output;
        }

        Log::error($process->getErrorOutput());
        return null;
    }

    public static function describeTable(string $table): array
    {
        $result = self::executeQuery("DESCRIBE TABLE {$table}");
        return $result ? collect(explode("\n", $result))
            ->map(function (string $line) {
                $line = trim($line);
                return [
                    'old_name' => Str::beforeLast($line, "\t"),
                    'new_name' => SqlAssistant::normalizeColumnName(Str::beforeLast($line, "\t")),
                    'type' => Str::replace("\'", "'", Str::afterLast($line, "\t")),
                ];
            })
            ->filter(fn(array $column) => $column['old_name'] !== '')
            ->sortBy('old_name')
            ->values()
            ->toArray() : [];
    }

    public static function numberOfRows(string $table): ?string
    {
        return self::executeQuery("SELECT COUNT(*) FROM {$table}");
    }
}
