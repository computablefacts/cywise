<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);
use App\Models\Collection;
use App\Models\File;

it('lists files for the current user', function () {
    asTenant1User();
    $collection = Collection::factory()->create();
    File::factory(2)->forCollection($collection)->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list')
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'page',
                'page_size',
                'nb_pages',
                'collection',
                'files',
            ],
        ]);

    expect($response->json('result.files'))->toBeArray()->toHaveCount(2);
    expect($response->json('result.page'))->toBe(1);
    expect($response->json('result.page_size'))->toBe(25);
    expect($response->json('result.collection'))->toBeNull();
});

it('returns an empty list when no files exist', function () {
    asTenant1User();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list');

    expect($response->json('result.files'))->toBeArray()->toHaveCount(0);
    expect($response->json('result.nb_pages'))->toBe(0);
});

it('paginates files with the page parameter', function () {
    asTenant1User();
    $collection = Collection::factory()->create();
    File::factory(5)->forCollection($collection)->create();

    $responsePage1 = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list', ['page' => 1, 'page_size' => 3]);

    $responsePage2 = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list', ['page' => 2, 'page_size' => 3]);

    expect($responsePage1->json('result.files'))->toHaveCount(3);
    expect($responsePage1->json('result.page'))->toBe(1);
    expect($responsePage2->json('result.files'))->toHaveCount(2);
    expect($responsePage2->json('result.page'))->toBe(2);
});

it('respects the page_size parameter', function () {
    asTenant1User();
    $collection = Collection::factory()->create();
    File::factory(4)->forCollection($collection)->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list', ['page_size' => 2]);

    expect($response->json('result.files'))->toHaveCount(2);
    expect($response->json('result.page_size'))->toBe(2);
    expect($response->json('result.nb_pages'))->toBe(2);
});

it('filters files by collection name', function () {
    asTenant1User();
    $colA = Collection::factory()->create(['name' => 'collection-a']);
    $colB = Collection::factory()->create(['name' => 'collection-b']);
    File::factory(2)->forCollection($colA)->create();
    File::factory(1)->forCollection($colB)->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list', ['collection' => 'collection-a']);

    expect($response->json('result.files'))->toHaveCount(2);
    expect($response->json('result.collection'))->toBe('collection-a');
});

it('does not list deleted files', function () {
    asTenant1User();
    $collection = Collection::factory()->create();
    File::factory()->forCollection($collection)->create();
    File::factory()->forCollection($collection)->deleted()->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list');

    expect($response->json('result.files'))->toHaveCount(1);
});

it('does not list files from deleted collections', function () {
    asTenant1User();
    $activeCollection = Collection::factory()->create();
    $deletedCollection = Collection::factory()->deleted()->create();
    File::factory()->forCollection($activeCollection)->create();
    File::factory()->forCollection($deletedCollection)->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list');

    expect($response->json('result.files'))->toHaveCount(1);
});

it('does not show files from another user private collection', function () {
    $user2 = tenant1User2();
    $privateColUser2 = Collection::factory()->create([
        'name' => "privcol{$user2->id}",
        'created_by' => $user2->id,
    ]);
    File::factory()->forCollection($privateColUser2)->create(['created_by' => $user2->id]);

    asTenant1User();
    $sharedCollection = Collection::factory()->create();
    File::factory()->forCollection($sharedCollection)->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list');

    expect($response->json('result.files'))->toHaveCount(1);
});

it('shows files from the current user own private collection', function () {
    asTenant1User();
    $privateCol = Collection::factory()->private()->create();
    File::factory(2)->forCollection($privateCol)->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list');

    expect($response->json('result.files'))->toHaveCount(2);
});

it('marks embedded files as processed', function () {
    asTenant1User();
    $collection = Collection::factory()->create();
    File::factory()->forCollection($collection)->embedded()->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list');

    expect($response->json('result.files.0.status'))->toBe('processed');
});

it('marks non-embedded files as processing', function () {
    asTenant1User();
    $collection = Collection::factory()->create();
    File::factory()->forCollection($collection)->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list');

    expect($response->json('result.files.0.status'))->toBe('processing');
});

it('returns correct file fields', function () {
    asTenant1User();
    $collection = Collection::factory()->create(['name' => 'my-collection']);
    File::factory()->forCollection($collection)->create([
        'name_normalized' => 'my-document',
        'extension' => 'pdf',
        'size' => 2048,
    ]);

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('files@list');

    $file = $response->json('result.files.0');
    expect($file)->toHaveKeys(['id', 'name_normalized', 'collection', 'filename', 'created_at', 'created_by', 'size', 'nb_chunks', 'nb_vectors', 'nb_not_vectors', 'status', 'download_url']);
    expect($file['name_normalized'])->toBe('my-document');
    expect($file['collection'])->toBe('my-collection');
    expect($file['filename'])->toBe('my-document.pdf');
    expect($file['size'])->toBe(2048);
});
