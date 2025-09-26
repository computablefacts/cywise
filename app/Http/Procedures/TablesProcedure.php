<?php

namespace App\Http\Procedures;

use App\Enums\StorageType;
use App\Events\ImportVirtualTable;
use App\Helpers\ClickhouseClient;
use App\Helpers\ClickhouseLocal;
use App\Helpers\ClickhouseUtils;
use App\Helpers\LlmProvider;
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
        $validated = $request->validate([
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
        /** @var User $user */
        $user = Auth::user();
        $count = TableStorage::dispatchImportTable($validated, $user);
        return [
            'message' => "{$count} table will be imported soon.",
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
        $validated = $request->validate([
            'query' => 'required|string|min:1|max:5000',
            'store' => 'required|boolean',
        ]);
        $user = Auth::user();
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
        $validated = $request->validate([
            'storage' => ['required', Rule::enum(StorageType::class)],
            'region' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'access_key_id' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'secret_access_key' => 'required_if:storage,' . StorageType::AWS_S3->value . '|string|min:0|max:100',
            'connection_string' => 'required_if:storage,' . StorageType::AZURE_BLOB_STORAGE->value . '|string|min:0|max:200',
            'input_folder' => 'required|string|min:0|max:100',
            'output_folder' => 'required|string|min:0|max:100',
        ]);
        $credentials = TableStorage::credentialsFromOptions($validated);
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
        $validated = $request->validate([
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
        $credentials = TableStorage::credentialsFromOptions($validated);
        $tables = collect($validated['tables']);
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
        $validated = $request->validate([
            'prompt' => 'required|string|min:1|max:5000',
        ]);
        /** @var User $user */
        $user = Auth::user();
        $prompt = $request->input('prompt');
        $query = ClickhouseUtils::promptToQuery(Table::where('created_by', $user->id)->get(), $prompt);

        if (empty($query)) {
            throw new \Exception('The query generation has failed.');
        }
        return [
            'query' => LlmProvider::cleanSqlQuery($query),
        ];
    }
}
