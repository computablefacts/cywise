<?php

namespace Tests\Unit\Procedures;

use App\Http\Controllers\Iframes\SharesController;
use App\Http\Procedures\AssetsProcedure;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Laravel;


it('returns an empty collection when no groups are provided', function () {
    $controller = new SharesController();
    $procedureMock = $this->createMock(AssetsProcedure::class);

    $result = $controller->getRows([], $procedureMock);

    expect($result)->toBeInstanceOf(Collection::class)->and($result->isEmpty())->toBeTrue();
});

it('handles missing created_by_email gracefully', function () {
    $controller = new SharesController();
    $procedureMock = $this->createMock(AssetsProcedure::class);
    $groups = [
        ['hash' => 'hash1', 'tags' => ['tag1']],
    ];

    $result = $controller->getRows($groups, $procedureMock);
    expect($result[0]['target'])->toEqual(__('Unknown'));
});

it('groups shares by hash and returns correct data', function () {
    $controller = new SharesController();
    $procedureMock = $this->createMock(AssetsProcedure::class);
    $procedureMock->method('assetsInGroup')->willReturn(['assets' => [1, 2]]);
    $procedureMock->method('vulnerabilitiesInGroup')->willReturn(['vulnerabilities' => [1]]);

    $groups = [
        ['hash' => 'user1@tenant2.com', 'tags' => ['tag1'], 'created_by_email' => 'user1@tenant1.com'],
        ['hash' => 'user1@tenant2.com', 'tags' => ['tag2'], 'created_by_email' => 'user1@tenant1.com'],
        ['hash' => 'user2@tenant2.com', 'tags' => ['tag1'], 'created_by_email' => 'user2@tenant1.com'],
    ];

    $result = $controller->getRows($groups, $procedureMock);

    expect($result)->toHaveCount(2);
    expect($result[0]['group'])->toEqual('user1@tenant2.com');
    expect($result[0]['tags'])->toEqual(['tag1', 'tag2']);
    expect($result[0]['nb_assets'])->toEqual(2);
    expect($result[0]['nb_vulnerabilities'])->toEqual(1);
    expect($result[0]['target'])->toEqual('user1@tenant1.com');

    expect($result[1]['group'])->toEqual('user2@tenant2.com');
    expect($result[1]['tags'])->toEqual(['tag1']);
    expect($result[1]['nb_assets'])->toEqual(2);
    expect($result[1]['nb_vulnerabilities'])->toEqual(1);
    expect($result[1]['target'])->toEqual('user2@tenant1.com');
});

it('avoid assets duplication in assets count', function () {
    $controller = new SharesController();
    $procedureMock = $this->createMock(AssetsProcedure::class);
    $procedureMock->method('assetsInGroup')->willReturn(['assets' => [123]]);
    $procedureMock->method('vulnerabilitiesInGroup')->willReturn(['vulnerabilities' => [1, 2, 3]]);

    $groups = [
        ['hash' => 'user1@tenant2.com', 'tags' => ['tag1'], 'created_by_email' => 'user1@tenant1.com'],
        ['hash' => 'user1@tenant2.com', 'tags' => ['tag1'], 'created_by_email' => 'user1@tenant1.com'],
    ];

    $result = $controller->getRows($groups, $procedureMock);

    // dump($result->toArray());

    expect($result)->toHaveCount(1);
    expect($result[0]['nb_assets'])->toEqual(1);
    expect($result[0]['nb_vulnerabilities'])->toEqual(3);
});

