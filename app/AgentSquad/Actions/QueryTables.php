<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\ThoughtActionObservation;
use App\Http\Procedures\TablesProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Table;
use App\Models\User;

class QueryTables extends AbstractAction
{
    protected function schema(): array
    {
        $tables = Table::query()
            ->get()
            ->map(function (Table $table) {
                $schema = collect($table->schema)
                    ->map(fn(array $column) => $column['new_name'])
                    ->join(",");
                return "{$table->name}: {$table->description} (schema={$schema})";
            })
            ->join("\n- ");

        return [
            "type" => "function",
            "function" => [
                "name" => "query_tables",
                "description" => "Answer analytical questions by querying structured tables. The following tables and schemas are available:\n- {$tables}",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "question" => [
                            "type" => "string",
                            "description" => "An analytical question.",
                        ],
                    ],
                    "required" => ["question"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct()
    {
        //
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        $chainOfThought = [];
        try {

            $procedure = new TablesProcedure();

            $request = new JsonRpcRequest(['prompt' => $input]);
            $request->setUserResolver(fn() => $user);
            /** @var string $query */
            $query = $procedure->promptToQuery($request)['query'];
            $chainOfThought[] = new ThoughtActionObservation("Transforming the user's input to SQL.", "prompt_to_query[{$input}]", "The output SQL is:\n{$query}");

            $request = new JsonRpcRequest([
                'query' => $query,
                'store' => false,
            ]);
            $request->setUserResolver(fn() => $user);
            /** @var array $tsv */
            $tsv = $procedure->executeSqlQuery($request)['data'];
            $nbRows = count($tsv);
            $chainOfThought[] = new ThoughtActionObservation("Executing SQL statement.", "execute_sql_query[{$query}]", "The query returned {$nbRows} row(s).");
            $table = collect($tsv)->map(fn(array $columns) => implode("\t", $columns))->join("\n");

            return new SuccessfulAnswer("The answer to '{$input}' in TSV format is (first row = header):\n{$table}", $chainOfThought);

        } catch (\Exception $e) {
            return new FailedAnswer("Computing the answer to '{$input}' ended in an error: {$e->getMessage()}", $chainOfThought);
        }
    }
}
