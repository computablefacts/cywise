<?php

use App\Models\Asset;
use App\Models\Scan;

it('creates a scan with default state', function () {
    $scan = Scan::factory()->create();

    expect($scan->asset_id)->not()->toBeNull();
    expect(Asset::query()->whereKey($scan->asset_id)->exists())->toBeTrue();

    expect($scan->ports_scan_id)->toBeNull();
    expect($scan->ports_scan_begins_at)->toBeNull();
    expect($scan->ports_scan_ends_at)->toBeNull();
    expect($scan->vulns_scan_id)->toBeNull();
    expect($scan->vulns_scan_begins_at)->toBeNull();
    expect($scan->vulns_scan_ends_at)->toBeNull();
});

it('creates a scan with ports scan started state', function () {
    $scan = Scan::factory()->portsScanStarted()->create();

    expect($scan->ports_scan_id)->not()->toBeNull();
    expect($scan->vulns_scan_id)->toBeNull();

    expect($scan->ports_scan_begins_at)->not()->toBeNull();
    expect($scan->ports_scan_ends_at)->toBeNull();
    expect($scan->vulns_scan_begins_at)->toBeNull();
    expect($scan->vulns_scan_ends_at)->toBeNull();
});

it('creates a scan with ports scan ended state', function () {
    $scan = Scan::factory()->portsScanEnded()->create();

    expect($scan->ports_scan_id)->not()->toBeNull();
    expect($scan->vulns_scan_id)->toBeNull();

    expect($scan->ports_scan_begins_at)->not()->toBeNull();
    expect($scan->ports_scan_ends_at)->not()->toBeNull();
    expect($scan->vulns_scan_begins_at)->toBeNull();
    expect($scan->vulns_scan_ends_at)->toBeNull();

    // Order: ports begins < ports ends
    expect($scan->ports_scan_begins_at->lt($scan->ports_scan_ends_at))->toBeTrue();
});

it('creates a scan with vulns scan started state', function () {
    $scan = Scan::factory()->vulnsScanStarted()->create();

    expect($scan->ports_scan_id)->not()->toBeNull();
    expect($scan->vulns_scan_id)->not()->toBeNull();

    expect($scan->ports_scan_begins_at)->not()->toBeNull();
    expect($scan->ports_scan_ends_at)->not()->toBeNull();
    expect($scan->vulns_scan_begins_at)->not()->toBeNull();
    expect($scan->vulns_scan_ends_at)->toBeNull();

    // Order: ports begins < ports ends < vulns begins
    expect($scan->ports_scan_begins_at->lt($scan->ports_scan_ends_at))->toBeTrue();
    expect($scan->ports_scan_ends_at->lt($scan->vulns_scan_begins_at))->toBeTrue();
});

it('creates a scan with vulns scan ended state', function () {
    $scan = Scan::factory()->vulnsScanEnded()->create();

    expect($scan->ports_scan_id)->not()->toBeNull();
    expect($scan->vulns_scan_id)->not()->toBeNull();

    expect($scan->ports_scan_begins_at)->not()->toBeNull();
    expect($scan->ports_scan_ends_at)->not()->toBeNull();
    expect($scan->vulns_scan_begins_at)->not()->toBeNull();
    expect($scan->vulns_scan_ends_at)->not()->toBeNull();

    // Order: ports begins < ports ends < vulns begins < vulns ends
    expect($scan->ports_scan_begins_at->lt($scan->ports_scan_ends_at))->toBeTrue();
    expect($scan->ports_scan_ends_at->lt($scan->vulns_scan_begins_at))->toBeTrue();
    expect($scan->vulns_scan_begins_at->lt($scan->vulns_scan_ends_at))->toBeTrue();
});

test('port scan ID should be next scan ID for asset when a port scan starts', function () {
    $scan = Scan::factory()->portsScanStarted()->create();

    $asset = $scan->asset;
    expect($asset->next_scan_id)->toBe($scan->ports_scan_id);
});

test('port scan ID should be next scan ID for asset when a port scan ends', function () {
    $scan = Scan::factory()->portsScanEnded()->create();

    $asset = $scan->asset;
    expect($asset->next_scan_id)->toBe($scan->ports_scan_id);
});

test('port scan ID should be next scan ID for asset when a vuln scan starts', function () {
    $scan = Scan::factory()->vulnsScanStarted()->create();

    $asset = $scan->asset;
    expect($asset->next_scan_id)->toBe($scan->ports_scan_id);
});

test('port scan ID should be current scan ID for asset when a vuln scan ends', function () {
    $scan = Scan::factory()->vulnsScanEnded()->create();

    $asset = $scan->asset;
    expect($asset->cur_scan_id)->toBe($scan->ports_scan_id);
    expect($asset->next_scan_id)->toBeNull();
});
