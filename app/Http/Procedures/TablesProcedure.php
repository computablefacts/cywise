<?php

namespace App\Http\Procedures;

use App\Enums\StorageType;
use App\Events\ImportVirtualTable;
use App\Helpers\ClickhouseClient;
use App\Helpers\ClickhouseLocal;
use App\Helpers\ClickhouseUtils;
use App\Helpers\TableStorage;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class TablesProcedure extends Procedure
{
    public static string $name = 'tables';

    #[RpcMethod(
        description: "List the available tables.",
        params: [],
        result: [
            "tables" => "An array of tables.",
        ]
    )]
    public function list(JsonRpcRequest $request): array
    {
        return [
            'tables' => Table::query()
                ->orderBy('name')
                ->get()
                ->map(fn(Table $table) => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'nb_rows' => \Illuminate\Support\Number::format($table->nb_rows, locale: 'sv'),
                    'nb_columns' => count($table->schema),
                    'description' => $table->description,
                    'last_update' => $table->finished_at ? $table->finished_at->format('Y-m-d H:i') : '',
                    'status' => $table->status(),
                ]),
        ];
    }

    #[RpcMethod(
        description: "Import one or more tables.",
        params: [
            'storage' => 'The type of storage (AWS S3 or Azure Blob Storage).',
            'region' => 'The AWS/Azure region.',
            'access_key_id' => 'The access key (AWS only).',
            'secret_access_key' => 'The secret key (AWS only).',
            'connection_string' => 'The connection string to the storage account (Azure only).',
            'input_folder' => 'Where the input files will be read.',
            'output_folder' => 'Where the output (or temporary) files will be written.',
            'tables' => [
                [
                    'table' => 'The table name.',
                    'old_name' => 'The old table name.',
                    'new_name' => 'The new table name.',
                    'type' => 'The table type (materialized or view).',
                ]
            ],
            'updatable' => '',
            'copy' => '',
            'deduplicate' => '',
            'description' => '',
        ],
        result: [
            "message" => "A success message.",
        ]
    )]
    public function import(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'storage' => ['required', Rule::enum(StorageType::class)],
            'region' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'access_key_id' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'secret_access_key' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'connection_string' => 'required_if:storage,' . StorageType::AZURE_BLOB_STORAGE->value . '|string|min:0|max:200',
            'input_folder' => 'string|min:0|max:100',
            'output_folder' => 'string|min:0|max:100',
            'tables' => 'required|array|min:1|max:500',
            'tables.*.table' => 'required|string|min:1|max:100',
            'tables.*.old_name' => 'required|string|min:1|max:100',
            'tables.*.new_name' => 'required|string|min:1|max:100',
            'tables.*.type' => 'required|string|min:1|max:50',
            'updatable' => 'required|boolean',
            'copy' => 'required|boolean',
            'deduplicate' => 'required|boolean',
            'description' => 'required|string|min:1',
        ]);
        $user = $request->user();
        $params = $this->fixupLocalFiles($user, $params);
        $count = TableStorage::dispatchImportTable($params, $user);
        return [
            'message' => "{$count} table will be imported soon.",
        ];
    }

    #[RpcMethod(
        description: "Force the import of a given table.",
        params: [
            'table_id' => 'The identifier of the table to reimport.',
        ],
        result: [
            "message" => "A success message.",
        ]
    )]
    public function forceImport(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'table_id' => 'required|int|exists:cb_tables,id',
        ]);

        /** @var Table $table */
        $table = Table::where('id', $params['table_id'])->firstOrFail();

        if (!empty($table->last_error)) {
            throw new \Exception('Tables with errors cannot be reimported.');
        }
        if (empty($table->last_warning)) {
            throw new \Exception('Tables without warnings cannot be reimported.');
        }

        $isRowcountWarning = Str::contains($table->last_warning, 'The number of rows differs by more than 10%', true);
        $isMissingColumnsWarning = Str::contains($table->last_warning, 'The new file must contain at least all the current table columns', true);

        if (!$isRowcountWarning && !$isMissingColumnsWarning) {
            throw new \Exception('These warnings cannot be bypassed.');
        }
        if ($isRowcountWarning) {
            $table->bypass_rowcount_warning = true;
            $table->save();
        } elseif ($isMissingColumnsWarning) {
            $table->bypass_missing_columns_warning = true;
            $table->save();
        }

        $params = array_merge($table->credentials, [
            'tables' => collect($table->schema)->map(fn(array $column) => array_merge($column, ['table' => $table->name]))->values()->all(),
            'updatable' => $table->updatable,
            'copy' => $table->copied,
            'deduplicate' => $table->deduplicated,
            'description' => $table->description,
        ]);
        $count = TableStorage::dispatchImportTable($params, $request->user());

        return [
            'message' => "{$count} table will be reimported soon.",
        ];
    }

    #[RpcMethod(
        description: "Execute a SQL query.",
        params: [
            'query' => 'The SQL query.',
            'store' => 'Whether to store the query as a virtual or physical table (optional).',
            'materialize' => 'Whether to store the query as a physical table (mandatory if store is true).',
            'name' => 'The name of the virtual or physical table (mandatory if store is true).',
            'description' => 'The description of the virtual or physical table (mandatory if store is true).',
            'format' => 'The format of the query (arrays, arrays_with_header or objects) (mandatory if store is false).',
        ],
        result: [
            'message' => 'A success message.',
            'data' => 'The requested data.',
        ]
    )]
    public function executeSqlQuery(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'query' => 'required|string|min:1|max:5000',
            'store' => 'required|boolean',
        ]);
        $user = $request->user();
        $name = $request->input('name', 'v_table');
        $description = $request->input('description', '');
        $query = $request->input('query');
        $store = $request->boolean('store', false);
        $materialize = $request->boolean('materialize', false);

        if ($store) {
            if ($materialize) {
                ImportVirtualTable::dispatch($user, $name, $query, $description);
                return [
                    'message' => 'The table will be materialized soon.',
                    'data' => [],
                ];
            }

            $tableName = ClickhouseUtils::normalizeTableName($name);
            /** @var Table $tbl */
            $tbl = Table::updateOrCreate([
                'name' => $tableName,
                'created_by' => $user->id,
            ], [
                'name' => $tableName,
                'description' => $description,
                'copied' => $materialize,
                'deduplicated' => false,
                'last_error' => null,
                'started_at' => Carbon::now(),
                'finished_at' => null,
                'created_by' => $user->id,
                'query' => $query,
            ]);

            $output = ClickhouseClient::dropViewIfExists($tableName);

            if (!$output) {
                $tbl->last_error = 'Error #8';
                $tbl->save();
                throw new \Exception("The query cannot be stored.");
            }

            $query = "CREATE VIEW {$tableName} AS {$query}";
            $output = ClickhouseClient::executeQuery($query);

            if (!$output) {
                $tbl->last_error = 'Error #9';
                $tbl->save();
                throw new \Exception("The query cannot be stored.");
            }

            $tbl->last_error = null;
            $tbl->finished_at = Carbon::now();
            $tbl->schema = ClickhouseClient::describeTable($tableName);
            $tbl->nb_rows = ClickhouseClient::numberOfRows($tableName) ?? 0;
            $tbl->save();

            $query = "SELECT * FROM {$tableName} LIMIT 10 FORMAT TabSeparatedWithNames";
        } else {
            $query = "WITH t AS ({$query}) SELECT * FROM t LIMIT 10 FORMAT TabSeparatedWithNames";
        }

        $output = ClickhouseClient::executeQuery($query);

        if (!$output) {
            throw new \Exception(ClickhouseClient::getExecuteQueryLastError());
        }
        return [
            'message' => 'The query has been executed.',
            'data' => collect(explode("\n", $output))
                ->filter(fn(string $line) => $line !== '')
                ->map(fn(string $line) => explode("\t", $line))
                ->values()
                ->all(),
        ];
    }

    #[RpcMethod(
        description: "List the content of a given bucket.",
        params: [
            'storage' => 'The type of storage (AWS S3 or Azure Blob Storage).',
            'region' => 'The AWS/Azure region.',
            'access_key_id' => 'The access key (AWS only).',
            'secret_access_key' => 'The secret key (AWS only).',
            'connection_string' => 'The connection string to the storage account (Azure only).',
            'input_folder' => 'Where the input files will be read.',
            'output_folder' => 'Where the output (or temporary) files will be written.',
        ],
        result: [
            "files" => "An array of files.",
        ]
    )]
    public function listBucketContent(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'storage' => ['required', Rule::enum(StorageType::class)],
            'region' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'access_key_id' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'secret_access_key' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'connection_string' => 'required_if:storage,' . StorageType::AZURE_BLOB_STORAGE->value . '|string|min:0|max:200',
            'input_folder' => 'required|string|min:0|max:100',
            'output_folder' => 'required|string|min:0|max:100',
        ]);
        $params = $this->fixupLocalFiles($request->user(), $params);
        $credentials = TableStorage::credentialsFromOptions($params);
        $disk = TableStorage::inDisk($credentials);
        $diskFiles = $disk->files();
        $files = [];

        foreach ($diskFiles as $diskFile) {
            $extension = Str::trim(Str::lower(pathinfo($diskFile, PATHINFO_EXTENSION)));
            if (in_array($extension, ['tsv'])) { // only TSV files are allowed
                $files[] = [
                    'object' => $diskFile,
                    'size' => \Illuminate\Support\Number::format($disk->size($diskFile), locale: 'sv'),
                    'last_modified' => Carbon::createFromTimestamp($disk->lastModified($diskFile))->format('Y-m-d H:i') . ' UTC',
                ];
            }
        }
        return [
            'files' => collect($files)->sortBy('object')->values()->all(),
        ];
    }

    #[RpcMethod(
        description: "List the content of a given list of files (in a given bucket).",
        params: [
            'storage' => 'The type of storage (AWS S3 or Azure Blob Storage).',
            'region' => 'The AWS/Azure region.',
            'access_key_id' => 'The access key (AWS only).',
            'secret_access_key' => 'The secret key (AWS only).',
            'connection_string' => 'The connection string to the storage account (Azure only).',
            'input_folder' => 'Where the input files will be read.',
            'output_folder' => 'Where the output (or temporary) files will be written.',
            'tables' => 'An array of tables to inspect.',
        ],
        result: [
            "tables" => "An array of tables.",
        ]
    )]
    public function listFileContent(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'storage' => ['required', Rule::enum(StorageType::class)],
            'region' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'access_key_id' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'secret_access_key' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'connection_string' => 'required_if:storage,' . StorageType::AZURE_BLOB_STORAGE->value . '|string|min:0|max:200',
            'input_folder' => 'string|min:0|max:100',
            'output_folder' => 'string|min:0|max:100',
            'tables' => 'required|array|min:1|max:1',
            'tables.*' => 'required|string|min:0|max:250',
        ]);
        $params = $this->fixupLocalFiles($request->user(), $params);
        $credentials = TableStorage::credentialsFromOptions($params);
        $tables = collect($params['tables']);
        $columns = $tables->map(function (string $table) use ($credentials) {

            $clickhouseTable = TableStorage::inClickhouseTableFunction($credentials, $table);

            return [
                'table' => $table,
                'columns' => ClickhouseLocal::describeTable($clickhouseTable),
            ];
        });
        return [
            'tables' => collect($columns)->sortBy('table')->values()->all(),
        ];
    }

    #[RpcMethod(
        description: "Convert a prompt to a SQL query.",
        params: [
            'prompt' => 'The prompt.',
        ],
        result: [
            "query" => "The SQL query.",
        ]
    )]
    public function promptToQuery(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'prompt' => 'required|string|min:1|max:5000',
        ]);
        /** @var User $user */
        $user = Auth::user();
        $prompt = $params['prompt'];
        $query = ClickhouseUtils::promptToQuery(Table::where('created_by', $user->id)->get(), $prompt);

        if (empty($query)) {
            throw new \Exception('The query generation has failed.');
        }
        return [
            'query' => Str::rtrim($query, ';'),
        ];
    }

    #[RpcMethod(
        description: "Update a table description.",
        params: [
            'name' => 'The table name.',
            'description' => 'The new description.',
        ],
        result: [
            'message' => 'A success message.',
            'data' => 'The updated table object.',
        ]
    )]
    public function updateDescription(JsonRpcRequest $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:100',
            'description' => 'required|string|min:1|max:2000',
        ]);

        /** @var User $user */
        $user = Auth::user();

        /** @var Table|null $table */
        $table = Table::query()
            ->where('name', $validated['name'])
            ->first();

        if (!$table) {
            throw new \Exception('Table not found.');
        }

        $table->description = $validated['description'] ?? '';
        $table->save();

        return [
            'message' => 'The description has been updated.',
            'data' => [
                'name' => $table->name,
                'nb_rows' => \Illuminate\Support\Number::format($table->nb_rows, locale: 'sv'),
                'nb_columns' => count($table->schema),
                'description' => $table->description,
                'last_update' => $table->finished_at ? $table->finished_at->format('Y-m-d H:i') : '',
                'status' => $table->status(),
            ],
        ];
    }

    private function fixupLocalFiles(User $user, array $params): array
    {
        if ($params['storage'] === StorageType::AWS_S3->value && (
                $params['region'] === 'local-region' ||
                $params['access_key_id'] === 'local-access_key_id' ||
                $params['secret_access_key'] === 'local-secret_access_key')) {
            $params['region'] = config('filesystems.disks.tables-s3.region');
            $params['access_key_id'] = config('filesystems.disks.tables-s3.key');
            $params['secret_access_key'] = config('filesystems.disks.tables-s3.secret');
            $params['input_folder'] = config('filesystems.disks.tables-s3.bucket') . "/" . config('app.env') . "/tables/{$user->tenant_id}/{$user->id}/";
            $params['output_folder'] = config('filesystems.disks.tables-s3.bucket') . "/" . config('app.env') . "/tables/{$user->tenant_id}/";
        }
        return $params;
    }
}
