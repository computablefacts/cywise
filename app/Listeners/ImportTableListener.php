<?php

namespace App\Listeners;

use App\Events\ImportTable;
use App\Helpers\ClickhouseClient;
use App\Helpers\ClickhouseLocal;
use App\Helpers\ClickhouseUtils;
use App\Helpers\TableStorage;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportTableListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof ImportTable)) {
            throw new \Exception('Invalid event type!');
        }

        $user = $event->user;
        $credentials = $event->credentials;
        $updatable = $event->updatable;
        $copy = $event->copy;
        $deduplicate = $event->deduplicate;
        $table = $event->table;
        $columns = $event->columns;
        $description = $event->description;

        $user->actAs(); // otherwise the tenant will not be properly set

        // Misc. parameters
        $clickhouseHost = config('towerify.clickhouse.host');
        $clickhouseUsername = config('towerify.clickhouse.username');
        $clickhousePassword = config('towerify.clickhouse.password');
        $clickhouseDatabase = config('towerify.clickhouse.database');
        $normalizedTableName = ClickhouseUtils::normalizeTableName($table);
        $tableIn = TableStorage::inClickhouseTableFunction($credentials, $table);
        $uidSuffix = '_' . Str::random(10);
        $tableOut = TableStorage::outClickhouseTableFunction($credentials, $normalizedTableName, $uidSuffix);
        $colNames = collect($columns)->map(fn(array $column) => "{$column['old_name']} AS {$column['new_name']}")->join(",");
        $distinct = $deduplicate ? "DISTINCT" : "";

        Log::debug("Importing table {$table} from S3 to clickhouse server");
        Log::debug("Column names: {$colNames}");
        Log::debug("Input file: {$tableIn}");
        Log::debug("Output file: {$tableOut}");

        // Reference the table
        /** @var Table $tbl */
        $tbl = Table::updateOrCreate([
            'name' => $normalizedTableName,
            'created_by' => $user->id,
        ], [
            'name' => $normalizedTableName,
            'description' => $description,
            'copied' => $copy,
            'deduplicated' => $deduplicate,
            'last_error' => null,
            'last_warning' => null,
            'started_at' => Carbon::now(),
            'finished_at' => null,
            'created_by' => $user->id,
            'schema' => $columns,
            'updatable' => $updatable,
            'credentials' => $credentials,
        ]);

        try {

            // Validate that incoming file columns are a superset of the existing table columns
            $tableDescription = ClickhouseClient::describeTable($normalizedTableName);

            if (!empty($tableDescription)) {

                $prevColumnNames = collect($tableDescription)->map(fn(array $c) => $c['new_name'])->values()->all();
                $newColumnNames = collect($columns)->map(fn(array $c) => ClickhouseUtils::normalizeColumnName($c['new_name']))->values()->all();
                $missing = collect($prevColumnNames)->diff($newColumnNames)->values()->all();

                if (!empty($missing)) {
                    $missing = implode(', ', $missing);
                    $message = "The new file must contain at least all the current table columns ({$missing})";
                    if ($tbl->bypass_missing_columns_warning) {
                        Log::warning("Bypassing missing columns warning for table {$normalizedTableName}: {$message}");
                    } else {
                        $tbl->last_error = null;
                        $tbl->last_warning = $message;
                        $tbl->save();
                        return;
                    }
                }
            }

            // Transform the TSV file to a Parquet file and write it to the user-defined output directory
            $query = "INSERT INTO FUNCTION {$tableOut} SELECT {$distinct} {$colNames} FROM {$tableIn}";
            $output = ClickhouseLocal::executeQuery($query);

            if (!$output) {
                $tbl->last_error = 'Error #1';
                $tbl->save();
                return;
            }

            // Get the table schema from the parquet file
            $query = "DESCRIBE TABLE {$tableOut}";
            $output = ClickhouseLocal::executeQuery($query);

            if (!$output) {
                $tbl->last_error = 'Error #2';
                $tbl->save();
                return;
            }

            $schema = Str::replace("\'", "'", Str::replace("\n", ',', $output));

            // Row count sanity check (+/- 10%) before altering existing tables
            $prevRowCountStr = ClickhouseClient::numberOfRows($normalizedTableName);
            $prevRowCount = $prevRowCountStr ? intval($prevRowCountStr) : 0;

            if ($prevRowCount > 0) {

                $newRowCountStr = ClickhouseLocal::numberOfRows($tableOut);
                $newRowCount = $newRowCountStr ? intval($newRowCountStr) : 0;
                $diff = abs($newRowCount - $prevRowCount);

                if (($diff / $prevRowCount) > 0.10) {
                    $percent = round(($diff / max($prevRowCount, 1)) * 100, 2);
                    $message = "The number of rows differs by more than 10% (expected {$prevRowCount}, got {$newRowCount}, diff {$percent}%)";
                    if ($tbl->bypass_rowcount_warning) {
                        Log::warning("Bypassing rowcount warning for table {$normalizedTableName}: {$message}");
                    } else {
                        $tbl->last_error = null;
                        $tbl->last_warning = $message;
                        $tbl->save();
                        return;
                    }
                }
            }
            if ($copy) {

                // Instead of dropping the existing table, create a temporary table and fill it
                // Then, drop the existing table and rename the temporary table

                // Create the table structure in clickhouse server
                $query = "CREATE TABLE IF NOT EXISTS {$normalizedTableName}{$uidSuffix} ({$schema}) ENGINE = MergeTree() ORDER BY tuple() SETTINGS index_granularity = 8192";
                $output = ClickhouseClient::executeQuery($query);

                if (!$output) {
                    $tbl->last_error = 'Error #3';
                    $tbl->save();
                    return;
                }

                // Load the data in clickhouse server
                // https://clickhouse.com/docs/en/integrations/s3#remote-insert-using-clickhouse-local
                $query = "INSERT INTO TABLE FUNCTION remoteSecure('{$clickhouseHost}', '{$clickhouseDatabase}.{$normalizedTableName}{$uidSuffix}', '{$clickhouseUsername}', '{$clickhousePassword}') (*) SELECT * FROM {$tableOut}";
                $output = ClickhouseLocal::executeQuery($query);

                if (!$output) {
                    $tbl->last_error = 'Error #4';
                    $tbl->save();
                    return;
                }

                // Drop any existing table from clickhouse server
                $output = ClickhouseClient::dropTableIfExists($normalizedTableName);

                if (!$output) {
                    $tbl->last_error = 'Error #5';
                    $tbl->save();
                    return;
                }

                // Rename the newly created table with the old name
                $output = ClickhouseClient::renameTable("{$normalizedTableName}{$uidSuffix}", $normalizedTableName);

            } else {

                // Drop any existing table from clickhouse server
                $output = ClickhouseClient::dropTableIfExists($normalizedTableName);

                if (!$output) {
                    $tbl->last_error = 'Error #6';
                    $tbl->save();
                    return;
                }

                // Create a view over the Parquet file in clickhouse server
                $engineOut = TableStorage::outClickhouseTableEngine($credentials, $normalizedTableName, $uidSuffix);
                $query = "CREATE TABLE IF NOT EXISTS {$normalizedTableName} ({$schema}) ENGINE = {$engineOut}";
                $output = ClickhouseClient::executeQuery($query);
            }
            if (!$output) {
                $tbl->last_error = 'Error #7';
                $tbl->save();
                return;
            }

            $tbl->last_error = null;
            $tbl->last_warning = null;
            $tbl->bypass_rowcount_warning = false;
            $tbl->bypass_missing_columns_warning = false;
            $tbl->finished_at = Carbon::now();
            $tbl->nb_rows = ClickhouseClient::numberOfRows($normalizedTableName) ?? 0;
            $tbl->save();

            TableStorage::deleteOldOutFiles($credentials, $normalizedTableName, 10);

            // TODO : create tmp_* view in clickhouse server for backward compatibility

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
