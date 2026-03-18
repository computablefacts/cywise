<?php

namespace App\AgentSquad\Assistants;

use App\Models\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SqlAssistant
{
    private Collection $tables;
    private string $analyticalQuestion;

    public static function normalizeTableName(string $name): string
    {
        return Str::replace(['-', ' '], '_', Str::lower(Str::beforeLast(Str::afterLast($name, '/'), '.')));
    }

    public static function normalizeColumnName(string $name): string
    {
        return Str::upper(Str::replace([' '], '_', $name));
    }

    public static function use(): SqlAssistant
    {
        return new SqlAssistant();
    }

    public function withTables(Collection $tables): SqlAssistant
    {
        $this->tables = $tables;
        return $this;
    }

    public function withAnalyticalQuestion(string $question): SqlAssistant
    {
        $this->analyticalQuestion = $question;
        return $this;
    }

    public function sql(): string
    {
        return TextAssistant::use()
            ->withPrompt('default_clickhouse_query_generation', [
                'SCHEMA' => $this->tables
                    ->map(function (Table $table) {
                        $columns = collect($table->schema)
                            ->map(fn(array $column) => "- {$column['new_name']} ({$column['type']})")
                            ->join("\n");
                        return "Table: {$table->name}\nDescription: {$table->description}\nColonnes:\n{$columns}";
                    })
                    ->join("\n\n"),
                'QUESTION' => $this->analyticalQuestion,
            ])
            ->text();
    }
}