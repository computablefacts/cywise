<?php

namespace App\Helpers;

use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\PromptsProvider;
use App\Models\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClickhouseUtils
{
    private function __construct()
    {
        //
    }

    public static function normalizeTableName(string $name): string
    {
        return Str::replace(['-', ' '], '_', Str::lower(Str::beforeLast(Str::afterLast($name, '/'), '.')));
    }

    public static function normalizeColumnName(string $name): string
    {
        return Str::upper(Str::replace([' '], '_', $name));
    }

    public static function promptToQuery(Collection $tables, string $question): string
    {
        $prompt = PromptsProvider::provide('default_clickhouse_query_generation', [
            'SCHEMA' => $tables
                ->map(function (Table $table) {
                    $columns = collect($table->schema)
                        ->map(fn(array $column) => "- {$column['new_name']} ({$column['type']})")
                        ->join("\n");
                    return "Table: {$table->name}\nDescription: {$table->description}\nColonnes:\n{$columns}";
                })
                ->join("\n\n"),
            'QUESTION' => $question,
        ]);
        return LlmsProvider::provide($prompt);
    }
}
