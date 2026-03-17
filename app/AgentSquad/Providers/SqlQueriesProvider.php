<?php

namespace App\AgentSquad\Providers;

use App\AgentSquad\Assistant;
use App\Models\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SqlQueriesProvider extends AbstractProvider
{
    public static function normalizeTableName(string $name): string
    {
        return Str::replace(['-', ' '], '_', Str::lower(Str::beforeLast(Str::afterLast($name, '/'), '.')));
    }

    public static function normalizeColumnName(string $name): string
    {
        return Str::upper(Str::replace([' '], '_', $name));
    }

    public static function provide(Collection $tables, string $question): string
    {
        $before = microtime(true);

        $answer = Assistant::use()
            ->withPrompt('default_clickhouse_query_generation', [
                'SCHEMA' => $tables
                    ->map(function (Table $table) {
                        $columns = collect($table->schema)
                            ->map(fn(array $column) => "- {$column['new_name']} ({$column['type']})")
                            ->join("\n");
                        return "Table: {$table->name}\nDescription: {$table->description}\nColonnes:\n{$columns}";
                    })
                    ->join("\n\n"),
                'QUESTION' => $question,
            ])
            ->text();

        $after = microtime(true);

        self::traceSuccess('sql-queries', $before, $after);
        return $answer;
    }
}
