<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class JosianneClient
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

    public static function numberOfRows(string $table): string
    {
        return self::executeQuery("SELECT COUNT(*) FROM {$table}") ?? '0';
    }

    private static function cmd(string $query): string
    {
        $host = config('towerify.josianne.host');
        $username = config('towerify.josianne.username');
        $password = config('towerify.josianne.password');
        $database = config('towerify.josianne.database');
        return "clickhouse-client --host '{$host}' --secure --user '{$username}' --password '{$password}' --database '{$database}' --query \"{$query}\"";
    }
}
