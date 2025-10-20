<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ClickhouseClient
{
    private static ?string $executeQueryLastError = null;

    private function __construct()
    {
        //
    }

    public static function executeQuery(string $query): ?string
    {
        self::$executeQueryLastError = null;
        $process = Process::fromShellCommandline(self::cmd($query));
        $process->setTimeout(null);
        $process->run();

        if ($process->isSuccessful()) {
            $output = trim($process->getOutput());
            return empty($output) ? 'ok' : $output;
        }

        self::$executeQueryLastError = $process->getErrorOutput();
        Log::error(self::$executeQueryLastError);
        return null;
    }

    public static function getExecuteQueryLastError(): ?string
    {
        return self::$executeQueryLastError;
    }

    public static function createDatabase(): ?string
    {
        $host = config('towerify.clickhouse.host');
        $username = config('towerify.clickhouse.username');
        $password = config('towerify.clickhouse.password');
        $database = config('towerify.clickhouse.database');
        /** @var User $user */
        $user = \Auth::user();
        $tenant = $user ? "_{$user->tenant_id}" : '_0';
        $query = "CREATE DATABASE IF NOT EXISTS {$database}{$tenant}";
        $cmd = "clickhouse-client --host '{$host}' --secure --user '{$username}' --password '{$password}' --query \"{$query}\"";

        self::$executeQueryLastError = null;
        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(null);
        $process->run();

        if ($process->isSuccessful()) {
            $output = trim($process->getOutput());
            return empty($output) ? 'ok' : $output;
        }

        self::$executeQueryLastError = $process->getErrorOutput();
        Log::error(self::$executeQueryLastError);
        return null;
    }

    public static function showTables(): ?string
    {
        return self::executeQuery("SHOW TABLES");
    }

    public static function describeTable(string $table): array
    {
        $result = self::executeQuery("DESCRIBE TABLE {$table}");
        return $result ? collect(explode("\n", $result))
            ->map(function (string $line) {
                $line = trim($line);
                return [
                    'old_name' => Str::beforeLast($line, "\t"),
                    'new_name' => ClickhouseUtils::normalizeColumnName(Str::beforeLast($line, "\t")),
                    'type' => Str::replace("\'", "'", Str::afterLast($line, "\t")),
                ];
            })
            ->filter(fn(array $column) => $column['old_name'] !== '')
            ->sortBy('old_name')
            ->values()
            ->toArray() : [];
    }

    public static function dropTableIfExists(string $table): ?string
    {
        return self::executeQuery("DROP TABLE IF EXISTS {$table}");
    }

    public static function dropViewIfExists(string $view): ?string
    {
        return self::executeQuery("DROP VIEW IF EXISTS {$view}");
    }

    public static function renameTable(string $oldName, string $newName): ?string
    {
        return self::executeQuery("RENAME TABLE {$oldName} TO {$newName}");
    }

    public static function numberOfRows(string $table): string
    {
        return self::executeQuery("SELECT COUNT(*) FROM {$table}") ?? '0';
    }

    private static function cmd(string $query): string
    {
        $host = config('towerify.clickhouse.host');
        $username = config('towerify.clickhouse.username');
        $password = config('towerify.clickhouse.password');
        $database = config('towerify.clickhouse.database');
        /** @var User $user */
        $user = \Auth::user();
        $tenant = $user ? "_{$user->tenant_id}" : '_0';
        return "clickhouse-client --host '{$host}' --secure --user '{$username}' --password '{$password}' --database '{$database}{$tenant}' --query \"{$query}\"";
    }
}
