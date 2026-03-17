<?php

namespace App\Listeners;

use App\AgentSquad\Assistants\SqlAssistant;
use App\Events\ImportVirtualTable;
use App\Helpers\ClickhouseClient;
use App\Models\Table;
use App\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportVirtualTableListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof ImportVirtualTable)) {
            throw new \Exception('Invalid event type!');
        }

        $user = $event->user;
        $table = $event->table;
        $query = $event->query;
        $description = $event->description;

        $user->actAs(); // otherwise the tenant will not be properly set
        $user->notify(new Notification(__("Import of table :table started.", ['table' => $table])));

        $tableName = SqlAssistant::normalizeTableName($table);
        /** @var Table $tbl */
        $tbl = Table::updateOrCreate([
            'name' => $tableName,
            'created_by' => $user->id,
        ], [
            'name' => $tableName,
            'description' => $description,
            'copied' => true,
            'deduplicated' => false,
            'last_error' => null,
            'started_at' => Carbon::now(),
            'finished_at' => null,
            'created_by' => $user->id,
            'query' => $query
        ]);

        try {

            // Instead of dropping the existing table, create a temporary table and fill it
            // Then, drop the existing table and rename the temporary table
            $uid = Str::random(10);

            // Create the table in clickhouse server
            $query = "CREATE TABLE {$tableName}_{$uid} ENGINE = MergeTree() ORDER BY tuple() AS {$query}";
            $output = ClickhouseClient::executeQuery($query);

            if (!$output) {
                $tbl->last_error = 'Error #10';
                $tbl->save();
                $user->notify(new Notification(__("Import of table :table failed: :error", ['table' => $table, 'error' => $tbl->last_error])));
                return;
            }

            // Drop any existing table from clickhouse server
            $output = ClickhouseClient::dropTableIfExists($tableName);

            if (!$output) {
                $tbl->last_error = 'Error #11';
                $tbl->save();
                $user->notify(new Notification(__("Import of table :table failed: :error", ['table' => $table, 'error' => $tbl->last_error])));
                return;
            }

            // Rename the newly created table with the old name
            $output = ClickhouseClient::renameTable("{$tableName}_{$uid}", $tableName);

            if (!$output) {
                $tbl->last_error = 'Error #12';
                $tbl->save();
                $user->notify(new Notification(__("Import of table :table failed: :error", ['table' => $table, 'error' => $tbl->last_error])));
                return;
            }

            $tbl->last_error = null;
            $tbl->finished_at = Carbon::now();
            $tbl->schema = ClickhouseClient::describeTable($tableName);
            $tbl->nb_rows = ClickhouseClient::numberOfRows($tableName) ?? 0;
            $tbl->save();

            $user->notify(new Notification(__("Import of table :table finished successfully.", ['table' => $table])));

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
