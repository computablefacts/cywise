<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

test('tenant logo basename is normalized from tenant name', function () {
    $tenant = new Tenant([
        'name' => 'Société Générale Paris',
    ]);

    expect($tenant->logoFileBasename())->toBe('societe-generale-paris');
    expect($tenant->logoPath('png'))->toBe('tenants/logos/societe-generale-paris.png');
});

test('tenant custom logo path uses the first existing S3 asset derived from tenant name', function () {
    $tenant = new Tenant([
        'name' => 'Acme France',
    ]);

    $disk = Mockery::mock();

    Storage::shouldReceive('disk')
        ->once()
        ->with('images-s3')
        ->andReturn($disk);

    $disk->shouldReceive('exists')
        ->with('tenants/logos/acme-france.svg')
        ->once()
        ->andReturn(false);
    $disk->shouldReceive('exists')
        ->with('tenants/logos/acme-france.png')
        ->once()
        ->andReturn(true);
    $disk->shouldReceive('url')
        ->zeroOrMoreTimes();

    expect($tenant->customLogoPath())->toBe('tenants/logos/acme-france.png');
});

test('tenant custom logo url resolves the S3 URL when a logo exists', function () {
    $tenant = new Tenant([
        'name' => 'Acme France',
    ]);

    $disk = Mockery::mock();

    Storage::shouldReceive('disk')
        ->times(2)
        ->with('images-s3')
        ->andReturn($disk);

    $disk->shouldReceive('exists')
        ->with('tenants/logos/acme-france.svg')
        ->once()
        ->andReturn(false);
    $disk->shouldReceive('exists')
        ->with('tenants/logos/acme-france.png')
        ->once()
        ->andReturn(true);
    $disk->shouldReceive('url')
        ->with('tenants/logos/acme-france.png')
        ->once()
        ->andReturn('https://cdn.example.test/tenants/logos/acme-france.png');

    expect($tenant->customLogoUrl())->toBe('https://cdn.example.test/tenants/logos/acme-france.png');
});

test('tenant logo url falls back to the default logo when no S3 logo exists', function () {
    $tenant = new Tenant([
        'name' => 'Missing Logo Tenant',
    ]);

    $disk = Mockery::mock();

    Storage::shouldReceive('disk')
        ->once()
        ->with('images-s3')
        ->andReturn($disk);

    $disk->shouldReceive('exists')
        ->times(5)
        ->andReturn(false);

    expect($tenant->logoUrl())->toContain('/cywise/img/cywise.png');
});

test('tenant logo url uses the custom tenant logo when one exists', function () {
    $tenant = new Tenant([
        'name' => 'Acme France',
    ]);

    $disk = Mockery::mock();

    Storage::shouldReceive('disk')
        ->times(2)
        ->with('images-s3')
        ->andReturn($disk);

    $disk->shouldReceive('exists')
        ->with('tenants/logos/acme-france.svg')
        ->once()
        ->andReturn(true);
    $disk->shouldReceive('url')
        ->with('tenants/logos/acme-france.svg')
        ->once()
        ->andReturn('https://cdn.example.test/tenants/logos/acme-france.svg');

    expect($tenant->logoUrl())->toBe('https://cdn.example.test/tenants/logos/acme-france.svg');
});
