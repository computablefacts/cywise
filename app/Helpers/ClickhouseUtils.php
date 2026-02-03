<?php

namespace App\Helpers;

use App\AgentSquad\Providers\LlmsProvider;
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
        $schema = $tables->map(function (Table $table) {
            $columns = collect($table->schema)->map(function ($column) {
                return "- {$column['new_name']} ({$column['type']})";
            })->join("\n");
            return "Table: {$table->name}\nDescription: {$table->description}\nColonnes:\n{$columns}";
        })->join("\n\n");

        $prompt = "En utilisant le schéma de base de données suivant:
            
            {$schema}
            
            Génère une requête SQL ClickHouse pour répondre à la demande suivante: \"{$question}\".
            Retourne uniquement la requête SQL, sans markdown et sans explications.
            N'utilise pas de ` dans ta réponse.
        ";

        return LlmsProvider::provide($prompt);
    }
}
