<?php

use App\Enums\StorageType;
use App\Events\ImportTable;
use App\Helpers\TableStorage;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;

test('storage type form string', function () {
    expect(TableStorage::StorageTypeFormString('s3'))->toEqual(StorageType::AWS_S3);
    expect(TableStorage::StorageTypeFormString('azure'))->toEqual(StorageType::AZURE_BLOB_STORAGE);
});

test('credentials from options s3', function () {
    # Arrange
    $options = [
        'storage' => 's3',
        'region' => 'us-east-1',
        'access_key_id' => 'test_key',
        'secret_access_key' => 'test_secret',
        'input_folder' => 'input',
        'output_folder' => 'output',
    ];

    # Act
    $credentials = TableStorage::credentialsFromOptions($options);

    # Assert
    expect($credentials['storage'])->toEqual('s3');
    expect($credentials['region'])->toEqual('us-east-1');
    expect($credentials['access_key_id'])->toEqual('test_key');
    expect($credentials['secret_access_key'])->toEqual('test_secret');
    expect($credentials['input_folder'])->toEqual('input');
    expect($credentials['output_folder'])->toEqual('output');
});

test('credentials from options azure', function () {
    # Arrange
    $options = [
        'storage' => 'azure',
        'connection_string' => 'test_connection_string',
        'input_folder' => 'input',
        'output_folder' => 'output',
    ];

    # Act
    $credentials = TableStorage::credentialsFromOptions($options);

    # Assert
    expect($credentials['storage'])->toEqual('azure');
    expect($credentials['connection_string'])->toEqual('test_connection_string');
    expect($credentials['input_folder'])->toEqual('input');
    expect($credentials['output_folder'])->toEqual('output');
});

dataset('inDiskProvider', function () {
    return [
        's3' => [[
            'storage' => 's3',
            'region' => 'us-east-1',
            'access_key_id' => 'test_key',
            'secret_access_key' => 'test_secret',
            'input_folder' => 'my_bucket/my_input_dir',
        ]],
        'azure' => [[
            'storage' => 'azure',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=my_account;AccountKey=my_key;EndpointSuffix=core.windows.net',
            'input_folder' => 'my_bucket/my_input_dir',
        ]],
    ];
});

test('in disk', function (array $credentials) {
    # Act
    $disk = TableStorage::inDisk($credentials);

    # Assert
    expect($disk)->toBeInstanceOf(FilesystemAdapter::class);
})->with('inDiskProvider');

dataset('outDiskProvider', function () {
    return [
        's3' => [[
            'storage' => 's3',
            'region' => 'us-east-1',
            'access_key_id' => 'test_key',
            'secret_access_key' => 'test_secret',
            'output_folder' => 'my_bucket/my_output_dir',
        ]],
        'azure' => [[
            'storage' => 'azure',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=my_account;AccountKey=my_key;EndpointSuffix=core.windows.net',
            'output_folder' => 'my_bucket/my_output_dir',
        ]],
    ];
});

test('out disk', function (array $credentials) {
    # Act
    $disk = TableStorage::outDisk($credentials);

    # Assert
    expect($disk)->toBeInstanceOf(FilesystemAdapter::class);
})->with('outDiskProvider');

dataset('clickhouseTableFunctionOrEngineProvider', function () {
    return [
        's3' => [
            [
                'storage' => 's3',
                'region' => 'us-east-1',
                'access_key_id' => 'test_key',
                'secret_access_key' => 'test_secret',
                'input_folder' => 'my_input_bucket/my_input_dir',
                'output_folder' => 'my_output_bucket/my_output_dir',
            ],
            'test_table',
            '_uid',
            "s3('https://s3.us-east-1.amazonaws.com/my_input_bucket/my_input_dir/test_table', 'test_key', 'test_secret', 'TabSeparatedWithNames')",
            "S3('https://s3.us-east-1.amazonaws.com/my_input_bucket/my_input_dir/test_table', 'test_key', 'test_secret', 'TabSeparatedWithNames')",
            "s3('https://s3.us-east-1.amazonaws.com/my_output_bucket/my_output_dir/test_table_uid.parquet', 'test_key', 'test_secret', 'Parquet')",
            "S3('https://s3.us-east-1.amazonaws.com/my_output_bucket/my_output_dir/test_table_uid.parquet', 'test_key', 'test_secret', 'Parquet')",
        ],
        's3 trailing slash' => [
            [
                'storage' => 's3',
                'region' => 'us-east-1',
                'access_key_id' => 'test_key',
                'secret_access_key' => 'test_secret',
                'input_folder' => 'my_input_bucket/my_input_dir/',
                'output_folder' => 'my_output_bucket/my_output_dir/',
            ],
            'test_table',
            '_uid',
            "s3('https://s3.us-east-1.amazonaws.com/my_input_bucket/my_input_dir/test_table', 'test_key', 'test_secret', 'TabSeparatedWithNames')",
            "S3('https://s3.us-east-1.amazonaws.com/my_input_bucket/my_input_dir/test_table', 'test_key', 'test_secret', 'TabSeparatedWithNames')",
            "s3('https://s3.us-east-1.amazonaws.com/my_output_bucket/my_output_dir/test_table_uid.parquet', 'test_key', 'test_secret', 'Parquet')",
            "S3('https://s3.us-east-1.amazonaws.com/my_output_bucket/my_output_dir/test_table_uid.parquet', 'test_key', 'test_secret', 'Parquet')",
        ],
        's3 bucket root' => [
            [
                'storage' => 's3',
                'region' => 'us-east-1',
                'access_key_id' => 'test_key',
                'secret_access_key' => 'test_secret',
                'input_folder' => 'my_input_bucket',
                'output_folder' => 'my_output_bucket',
            ],
            'test_table',
            '_uid',
            "s3('https://s3.us-east-1.amazonaws.com/my_input_bucket/test_table', 'test_key', 'test_secret', 'TabSeparatedWithNames')",
            "S3('https://s3.us-east-1.amazonaws.com/my_input_bucket/test_table', 'test_key', 'test_secret', 'TabSeparatedWithNames')",
            "s3('https://s3.us-east-1.amazonaws.com/my_output_bucket/test_table_uid.parquet', 'test_key', 'test_secret', 'Parquet')",
            "S3('https://s3.us-east-1.amazonaws.com/my_output_bucket/test_table_uid.parquet', 'test_key', 'test_secret', 'Parquet')",
        ],
        's3 bucket root trailing slash' => [
            [
                'storage' => 's3',
                'region' => 'us-east-1',
                'access_key_id' => 'test_key',
                'secret_access_key' => 'test_secret',
                'input_folder' => 'my_input_bucket/',
                'output_folder' => 'my_output_bucket/',
            ],
            'test_table',
            '_uid',
            "s3('https://s3.us-east-1.amazonaws.com/my_input_bucket/test_table', 'test_key', 'test_secret', 'TabSeparatedWithNames')",
            "S3('https://s3.us-east-1.amazonaws.com/my_input_bucket/test_table', 'test_key', 'test_secret', 'TabSeparatedWithNames')",
            "s3('https://s3.us-east-1.amazonaws.com/my_output_bucket/test_table_uid.parquet', 'test_key', 'test_secret', 'Parquet')",
            "S3('https://s3.us-east-1.amazonaws.com/my_output_bucket/test_table_uid.parquet', 'test_key', 'test_secret', 'Parquet')",
        ],
        'azure' => [
            [
                'storage' => 'azure',
                'connection_string' => 'azure_connexion_string',
                'input_folder' => 'my_input_bucket/my_input_dir',
                'output_folder' => 'my_output_bucket/my_output_dir',
            ],
            'test_table',
            '_uid',
            "azureBlobStorage('azure_connexion_string', 'my_input_bucket', 'my_input_dir/test_table', 'TabSeparatedWithNames')",
            "AzureBlobStorage('azure_connexion_string', 'my_input_bucket', 'my_input_dir/test_table', 'TabSeparatedWithNames')",
            "azureBlobStorage('azure_connexion_string', 'my_output_bucket', 'my_output_dir/test_table_uid.parquet', 'Parquet')",
            "AzureBlobStorage('azure_connexion_string', 'my_output_bucket', 'my_output_dir/test_table_uid.parquet', 'Parquet')",
        ],
        'azure trailing slash' => [
            [
                'storage' => 'azure',
                'connection_string' => 'azure_connexion_string',
                'input_folder' => 'my_input_bucket/my_input_dir/',
                'output_folder' => 'my_output_bucket/my_output_dir/',
            ],
            'test_table',
            '_uid',
            "azureBlobStorage('azure_connexion_string', 'my_input_bucket', 'my_input_dir/test_table', 'TabSeparatedWithNames')",
            "AzureBlobStorage('azure_connexion_string', 'my_input_bucket', 'my_input_dir/test_table', 'TabSeparatedWithNames')",
            "azureBlobStorage('azure_connexion_string', 'my_output_bucket', 'my_output_dir/test_table_uid.parquet', 'Parquet')",
            "AzureBlobStorage('azure_connexion_string', 'my_output_bucket', 'my_output_dir/test_table_uid.parquet', 'Parquet')",
        ],
        'azure bucket root' => [
            [
                'storage' => 'azure',
                'connection_string' => 'azure_connexion_string',
                'input_folder' => 'my_input_bucket',
                'output_folder' => 'my_output_bucket',
            ],
            'test_table',
            '_uid',
            "azureBlobStorage('azure_connexion_string', 'my_input_bucket', 'test_table', 'TabSeparatedWithNames')",
            "AzureBlobStorage('azure_connexion_string', 'my_input_bucket', 'test_table', 'TabSeparatedWithNames')",
            "azureBlobStorage('azure_connexion_string', 'my_output_bucket', 'test_table_uid.parquet', 'Parquet')",
            "AzureBlobStorage('azure_connexion_string', 'my_output_bucket', 'test_table_uid.parquet', 'Parquet')",
        ],
        'azure bucket root trailing slash' => [
            [
                'storage' => 'azure',
                'connection_string' => 'azure_connexion_string',
                'input_folder' => 'my_input_bucket/',
                'output_folder' => 'my_output_bucket/',
            ],
            'test_table',
            '_uid',
            "azureBlobStorage('azure_connexion_string', 'my_input_bucket', 'test_table', 'TabSeparatedWithNames')",
            "AzureBlobStorage('azure_connexion_string', 'my_input_bucket', 'test_table', 'TabSeparatedWithNames')",
            "azureBlobStorage('azure_connexion_string', 'my_output_bucket', 'test_table_uid.parquet', 'Parquet')",
            "AzureBlobStorage('azure_connexion_string', 'my_output_bucket', 'test_table_uid.parquet', 'Parquet')",
        ],
    ];
});

test('in clickhouse table function', function (array $credentials, string $tableName, string $suffix, string $expectedInFunction) {
    # Act
    $result = TableStorage::inClickhouseTableFunction($credentials, $tableName);

    # Assert
    expect($result)->toEqual($expectedInFunction);
})->with('clickhouseTableFunctionOrEngineProvider');

test('out clickhouse table function', function (array $credentials, string $tableName, string $suffix, $unused1, $unused2, string $expectedOutFunction) {
    # Act
    $result = TableStorage::outClickhouseTableFunction($credentials, $tableName, $suffix);

    # Assert
    expect($result)->toEqual($expectedOutFunction);
})->with('clickhouseTableFunctionOrEngineProvider');

test('out clickhouse table engine', function (array $credentials, string $tableName, string $suffix, $unused1, $unused2, $unused3, string $expectedOutEngine) {
    # Act
    $result = TableStorage::outClickhouseTableEngine($credentials, $tableName, $suffix);

    # Assert
    expect($result)->toEqual($expectedOutEngine);
})->with('clickhouseTableFunctionOrEngineProvider');

test('dispatch import table', function () {
    # Arrange
    $validated = [
        'storage' => 's3',
        'region' => 'us-east-1',
        'access_key_id' => 'test_key',
        'secret_access_key' => 'test_secret',
        'input_folder' => 'input',
        'output_folder' => 'output',
        'tables' => [
            ['table' => 'table1', 'columns' => ['col1', 'col2']],
            ['table' => 'table2', 'columns' => ['col3', 'col4']],
        ],
        'copy' => true,
        'deduplicate' => false,
        'updatable' => true,
        'description' => 'test description',
    ];
    $user = new User();
    Event::fake();

    # Act
    $result = TableStorage::dispatchImportTable($validated, $user);

    # Assert
    expect($result)->toEqual(2);
    Event::assertDispatched(ImportTable::class, 2);
});
