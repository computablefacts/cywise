<?php

namespace Tests\Unit\WithDb;

use App\Enums\AssetTypesEnum;
use App\Helpers\ApiUtilsFacade as ApiUtils2;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Jobs\TriggerScan;
use App\Listeners\DeleteAssetListener;
use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\AssetTagHash;
use App\Models\Attacker;
use App\Models\HiddenAlert;
use App\Models\Honeypot;
use App\Models\HoneypotEvent;
use App\Models\Port;
use App\Models\PortTag;
use App\Models\Scan;
use App\Models\Screenshot;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ScansTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Disable tests with DB.');
    }
    protected function tearDown(): void
    {
        // $this->markTestSkipped('Disable tests with DB.');
    }

    public function testInvalidAssetsAreNotAdded()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->post("/api/inventory/assets", [
            'asset' => 'www+example+com',
            'watch' => false,
        ]);

        $response->assertStatus(500);

        $asset = Asset::where('asset', 'www+example+com')->first();

        $this->assertNull($asset);
    }

    public function testValidDnsAreAdded(): void
    {
        $response = $this->addDns();

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $this->assertEquals('www.example.com', $asset->asset);
        $this->assertEquals(AssetTypesEnum::DNS, $asset->type);
        $this->assertEquals('example.com', $asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertNull($asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertFalse($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);
    }

    public function testValidIpAreAdded(): void
    {
        $response = $this->addIp();

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $this->assertEquals('93.184.215.14', $asset->asset);
        $this->assertEquals(AssetTypesEnum::IP, $asset->type);
        $this->assertNull($asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertNull($asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertFalse($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);
    }

    public function testValidRangesAreAdded(): void
    {
        $response = $this->addRange();

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $this->assertEquals('255.255.255.255/32', $asset->asset);
        $this->assertEquals(AssetTypesEnum::RANGE, $asset->type);
        $this->assertNull($asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertNull($asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertFalse($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);
    }

    public function testTheDnsMonitoringBegins(): void
    {
        $response = $this->addDns();
        $response = $this->startMonitoringAsset(
            $response['asset']['uid'],
            $response['asset']['asset'],
            $response['asset']['tld'],
            $response['asset']['type']
        );

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $this->assertEquals('www.example.com', $asset->asset);
        $this->assertEquals(AssetTypesEnum::DNS, $asset->type);
        $this->assertEquals('example.com', $asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertNull($asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertTrue($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);
    }

    public function testTheIpMonitoringBegins(): void
    {
        $response = $this->addIp();
        $response = $this->startMonitoringAsset(
            $response['asset']['uid'],
            $response['asset']['asset'],
            $response['asset']['tld'],
            $response['asset']['type']
        );

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $this->assertEquals('93.184.215.14', $asset->asset);
        $this->assertEquals(AssetTypesEnum::IP, $asset->type);
        $this->assertNull($asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertNull($asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertTrue($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);
    }

    public function testTheRangeMonitoringBegins(): void
    {
        $response = $this->addRange();
        $response = $this->startMonitoringAsset(
            $response['asset']['uid'],
            $response['asset']['asset'],
            $response['asset']['tld'],
            $response['asset']['type']
        );

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $this->assertEquals('255.255.255.255/32', $asset->asset);
        $this->assertEquals(AssetTypesEnum::RANGE, $asset->type);
        $this->assertNull($asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertNull($asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertTrue($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);
    }

    public function testTheDnsMonitoringEnds(): void
    {
        $response = $this->addDns();
        $response = $this->startMonitoringAsset(
            $response['asset']['uid'],
            $response['asset']['asset'],
            $response['asset']['tld'],
            $response['asset']['type']
        );
        $response = $this->stopMonitoringAsset(
            $response['asset']['uid'],
            $response['asset']['asset'],
            $response['asset']['tld'],
            $response['asset']['type']
        );

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $this->assertEquals('www.example.com', $asset->asset);
        $this->assertEquals(AssetTypesEnum::DNS, $asset->type);
        $this->assertEquals('example.com', $asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertNull($asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertFalse($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);
    }

    public function testTheIpMonitoringEnds(): void
    {
        $response = $this->addIp();
        $response = $this->startMonitoringAsset(
            $response['asset']['uid'],
            $response['asset']['asset'],
            $response['asset']['tld'],
            $response['asset']['type']
        );
        $response = $this->stopMonitoringAsset(
            $response['asset']['uid'],
            $response['asset']['asset'],
            $response['asset']['tld'],
            $response['asset']['type']
        );

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $this->assertEquals('93.184.215.14', $asset->asset);
        $this->assertEquals(AssetTypesEnum::IP, $asset->type);
        $this->assertNull($asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertNull($asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertFalse($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);
    }

    public function testTheRangeMonitoringEnds(): void
    {
        $response = $this->addRange();
        $response = $this->startMonitoringAsset(
            $response['asset']['uid'],
            $response['asset']['asset'],
            $response['asset']['tld'],
            $response['asset']['type']
        );
        $response = $this->stopMonitoringAsset(
            $response['asset']['uid'],
            $response['asset']['asset'],
            $response['asset']['tld'],
            $response['asset']['type']
        );

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $this->assertEquals('255.255.255.255/32', $asset->asset);
        $this->assertEquals(AssetTypesEnum::RANGE, $asset->type);
        $this->assertNull($asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertNull($asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertFalse($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);
    }

    public function testItAddsTag(): void
    {
        $response = $this->addDns();

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $response = $this->addTag($asset->id, 'demo');

        $this->assertEquals(['demo'], $asset->tags()->get()->pluck('tag')->toArray());
    }

    public function testItRemovesTag(): void
    {
        $response = $this->addDns();

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $response = $this->addTag($asset->id, 'demo');
        $response = $this->removeTag($asset->id, $response[0]['id']);

        $this->assertEquals([], $asset->tags()->get()->pluck('tag')->toArray());
    }

    public function testTheDatabaseStaysCleanWhenThePortsScanFails()
    {
        // Mock external API calls
        $this->mockStartPortsScan();
        $this->mockGetPortsScanStatusWhenScanEndsWithAnError();

        // Setup the asset and trigger a scan
        $response = $this->addDns();

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $response = $this->startMonitoringAsset(
            $asset->id,
            $asset->asset,
            $asset->tld(),
            $asset->type->value
        );

        // Check the output of the /assets/infos endpoint (1/2)
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->get("/api/adversary/infos-from-asset/" . base64_encode('www.example.com'));

        $response->assertStatus(200)->assertJson(function (AssertableJson $json) use ($asset) {
            $json->where('asset', 'www.example.com')
                ->whereType('modifications', 'array')
                ->where('modifications.0.asset_id', $asset->id)
                ->where('modifications.0.asset_name', $asset->asset)
                ->whereType('modifications.0.timestamp', 'string')
                ->where('modifications.0.user', "qa@computablefacts.com")
                ->where('tags', [])
                ->where('ports', [])
                ->where('vulnerabilities', [])
                ->where('timeline.nmap.id', null)
                ->where('timeline.nmap.start', null)
                ->where('timeline.nmap.end', null)
                ->where('timeline.sentinel.id', null)
                ->where('timeline.sentinel.start', null)
                ->where('timeline.sentinel.end', null)
                ->whereType('timeline.next_scan', 'string')
                ->where('hiddenAlerts', [])
                ->etc();
        });

        $job = (new TriggerScan())->withFakeQueueInteractions();
        $job->handle();
        
        $asset->refresh();

        // Check tables content
        $this->assertEquals(1, Asset::count());
        $this->assertEquals(0, AssetTag::count());
        $this->assertEquals(0, AssetTagHash::count());
        $this->assertEquals(0, Attacker::count());
        $this->assertEquals(0, HiddenAlert::count());
        $this->assertEquals(0, Honeypot::count());
        $this->assertEquals(0, HoneypotEvent::count());
        $this->assertEquals(0, Port::count());
        $this->assertEquals(0, PortTag::count());
        $this->assertEquals(0, Scan::count());
        $this->assertEquals(0, Screenshot::count());
    }

    public function testItScansAnAssetAndProperlyDealsWithClosedPorts()
    {
        // Mock external API calls
        $this->mockStartPortsScan();
        $this->mockGetPortsScanStatus();
        $this->mockGetPortsScanResult();
        $this->mockGetIpOwner();
        $this->mockGetIpGeoloc();
        $this->mockStartVulnsScanOnPort80();
        $this->mockStartVulnsScanOnPort443();
        $this->mockGetVulnsScanResultWhenPort80IsClosed();
        $this->mockGetVulnsScanResultOnPort443();
        $this->mockTranslate();

        // Setup the asset and trigger a scan
        $response = $this->addDns();

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $response = $this->addTag($asset->id, 'demo');
        $response = $this->startMonitoringAsset(
            $asset->id,
            $asset->asset,
            $asset->tld(),
            $asset->type->value
        );

        // Check the output of the /assets/infos endpoint (1/2)
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->get("/api/adversary/infos-from-asset/" . base64_encode('www.example.com'));

        $response->assertStatus(200)->assertJson(function (AssertableJson $json) use ($asset) {
            $json->where('asset', 'www.example.com')
                ->whereType('modifications', 'array')
                ->where('modifications.0.asset_id', $asset->id)
                ->where('modifications.0.asset_name', $asset->asset)
                ->whereType('modifications.0.timestamp', 'string')
                ->where('modifications.0.user', "qa@computablefacts.com")
                ->where('tags', ['demo'])
                ->where('ports', [])
                ->where('vulnerabilities', [])
                ->where('timeline.nmap.id', null)
                ->where('timeline.nmap.start', null)
                ->where('timeline.nmap.end', null)
                ->where('timeline.sentinel.id', null)
                ->where('timeline.sentinel.start', null)
                ->where('timeline.sentinel.end', null)
                ->whereType('timeline.next_scan', 'string')
                ->where('hiddenAlerts', [])
                ->etc();
        });

        $job = (new TriggerScan())->withFakeQueueInteractions();
        $job->handle();

        $asset->refresh();

        // Check tables content
        $this->assertEquals(1, Asset::count());
        $this->assertEquals(1, AssetTag::count());
        $this->assertEquals(0, AssetTagHash::count());
        $this->assertEquals(0, Attacker::count());
        $this->assertEquals(0, HiddenAlert::count());
        $this->assertEquals(0, Honeypot::count());
        $this->assertEquals(0, HoneypotEvent::count());
        $this->assertEquals(2, Port::count());
        $this->assertEquals(10, PortTag::count());
        $this->assertEquals(2, Scan::count());
        $this->assertEquals(1, Screenshot::count());

        // Check the assets table
        $this->assertEquals('www.example.com', $asset->asset);
        $this->assertEquals(AssetTypesEnum::DNS, $asset->type);
        $this->assertEquals('example.com', $asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertEquals('6409ae68ed42e11e31e5f19d', $asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertTrue($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);

        // Check the assets_tags table
        $this->assertEquals(['demo'], $asset->tags()->get()->pluck('tag')->toArray());

        // Check the ports table
        $ports = $asset->ports()->get();
        /** @var Port $port80 */
        $port80 = $ports->filter(fn(Port $port) => $port->port === 80)->first();
        /** @var Port $port443 */
        $port443 = $ports->filter(fn(Port $port) => $port->port === 443)->first();
        $screenshot80 = $port80->screenshot()->first();
        $screenshot443 = $port443->screenshot()->first();

        $this->assertNull($screenshot80);
        $this->assertEquals($port443->id, $screenshot443->port_id);

        $this->assertEquals('www.example.com', $port80->hostname);
        $this->assertEquals('93.184.215.14', $port80->ip);
        $this->assertEquals(80, $port80->port);
        $this->assertEquals('tcp', $port80->protocol);
        $this->assertEquals('US', $port80->country);
        $this->assertNull($port80->service);
        $this->assertNull($port80->product);
        $this->assertNull($port80->ssl);
        $this->assertEquals('EDGECAST, US', $port80->hosting_service_description);
        $this->assertEquals('ripencc', $port80->hosting_service_registry);
        $this->assertEquals('15133', $port80->hosting_service_asn);
        $this->assertEquals('93.184.215.0/24', $port80->hosting_service_cidr);
        $this->assertEquals('US', $port80->hosting_service_country_code);
        $this->assertEquals('2008-06-02', $port80->hosting_service_date);
        $this->assertTrue($port80->closed);

        $this->assertEquals('www.example.com', $port443->hostname);
        $this->assertEquals('93.184.215.14', $port443->ip);
        $this->assertEquals(443, $port443->port);
        $this->assertEquals('tcp', $port443->protocol);
        $this->assertEquals('US', $port443->country);
        $this->assertEquals('http', $port443->service);
        $this->assertEquals('ECAcc (bsb|2789)', $port443->product);
        $this->assertTrue($port443->ssl);
        $this->assertEquals('EDGECAST, US', $port443->hosting_service_description);
        $this->assertEquals('ripencc', $port443->hosting_service_registry);
        $this->assertEquals('15133', $port443->hosting_service_asn);
        $this->assertEquals('93.184.215.0/24', $port443->hosting_service_cidr);
        $this->assertEquals('US', $port443->hosting_service_country_code);
        $this->assertEquals('2008-06-02', $port443->hosting_service_date);
        $this->assertFalse($port443->closed);

        // Check the ports_tags table
        /** @var array $tags80 */
        $tags80 = $port80->tags()->orderBy('tag')->get()->pluck('tag')->toArray();
        /** @var array $tags443 */
        $tags443 = $port443->tags()->orderBy('tag')->get()->pluck('tag')->toArray();

        $this->assertEquals([], $tags80);
        $this->assertEquals(['azure', 'azure cdn', 'demo', 'ecacc (bsb/27bf)', 'http', 'ssl-issuer|digicert inc', 'tls10', 'tls11', 'tls12', 'tls13'], $tags443);

        // Check the scans table
        $scans = $asset->scanCompleted();
        /** @var Scan $scan80 */
        $scan80 = $scans->filter(fn(Scan $scan) => $scan->vulns_scan_id === 'b9b5e877-bdfe-4b39-8c4b-8316e451730e')->first();
        /** @var Scan $scan443 */
        $scan443 = $scans->filter(fn(Scan $scan) => $scan->vulns_scan_id === 'a9a5d877-abed-4a39-8b4a-8316d451730d')->first();

        $this->assertEquals('6409ae68ed42e11e31e5f19d', $scan80->ports_scan_id);
        $this->assertEquals('b9b5e877-bdfe-4b39-8c4b-8316e451730e', $scan80->vulns_scan_id);
        $this->assertNotNull($scan80->ports_scan_begins_at);
        $this->assertNotNull($scan80->ports_scan_ends_at);
        $this->assertNotNull($scan80->vulns_scan_begins_at);
        $this->assertNotNull($scan80->vulns_scan_ends_at);
        $this->assertEquals($asset->id, $scan80->asset_id);

        $this->assertEquals('6409ae68ed42e11e31e5f19d', $scan443->ports_scan_id);
        $this->assertEquals('a9a5d877-abed-4a39-8b4a-8316d451730d', $scan443->vulns_scan_id);
        $this->assertNotNull($scan443->ports_scan_begins_at);
        $this->assertNotNull($scan443->ports_scan_ends_at);
        $this->assertNotNull($scan443->vulns_scan_begins_at);
        $this->assertNotNull($scan443->vulns_scan_ends_at);
        $this->assertEquals($asset->id, $scan443->asset_id);

        // Check the output of the /assets/infos endpoint (2/2)
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->get("/api/adversary/infos-from-asset/" . base64_encode('www.example.com'));

        $response->assertStatus(200)->assertJson(function (AssertableJson $json) use ($asset, $scan80, $scan443, $screenshot80, $screenshot443) {
            $json->where('asset', 'www.example.com')
                ->whereType('modifications', 'array')
                ->where('modifications.0.asset_id', $asset->id)
                ->where('modifications.0.asset_name', $asset->asset)
                ->whereType('modifications.0.timestamp', 'string')
                ->where('modifications.0.user', "qa@computablefacts.com")
                ->where('tags', ['demo'])
                ->whereType('ports', 'array')
                ->where('ports.0.ip', '93.184.215.14')
                ->where('ports.0.port', 443)
                ->where('ports.0.protocol', 'tcp')
                ->where('ports.0.products.0', 'ECAcc (bsb|2789)')
                ->where('ports.0.services.0', 'http')
                ->where('ports.0.tags', ['azure', 'azure cdn', 'demo', 'ecacc (bsb/27bf)', 'http', 'ssl-issuer|digicert inc', 'tls10', 'tls11', 'tls12', 'tls13'])
                ->where('ports.0.screenshotId', $screenshot443->id)
                ->whereType('vulnerabilities', 'array')
                ->where('vulnerabilities.0.ip', '93.184.215.14')
                ->where('vulnerabilities.0.port', 443)
                ->where('vulnerabilities.0.protocol', 'tcp')
                ->where('vulnerabilities.0.type', 'weak_cipher_suites_v3_alert')
                ->where('vulnerabilities.0.tested', false)
                ->where('vulnerabilities.0.vulnerability', "A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href='https://www.example.com:443' target='_blank'>https://www.example.com:443</a></li></ul>")
                ->where('vulnerabilities.0.remediation', "Fix the vulnerability described in this alert")
                ->where('vulnerabilities.0.level', 'low')
                ->where('vulnerabilities.0.uid', 'a2a95bb1311b66abb394ac9015175dcd')
                ->where('vulnerabilities.0.cve_id', null)
                ->where('vulnerabilities.0.cve_cvss', null)
                ->where('vulnerabilities.0.cve_vendor', null)
                ->where('vulnerabilities.0.cve_product', null)
                ->where('vulnerabilities.0.title', "Weak Cipher Suites Detection")
                ->where('vulnerabilities.0.flarum_url', null)
                ->whereType('vulnerabilities.0.start_date', "string")
                ->where('vulnerabilities.0.is_hidden', false)
                ->where('timeline.nmap.id', '6409ae68ed42e11e31e5f19d')
                ->whereType('timeline.nmap.start', "string")
                ->whereType('timeline.nmap.end', "string")
                ->where('timeline.sentinel.id', '000000000000000000000000')
                ->whereType('timeline.sentinel.start', "string")
                ->whereType('timeline.sentinel.end', "string")
                ->whereType('timeline.next_scan', "string")
                ->where('hiddenAlerts', [])
                ->etc();
        });

        // Remove the asset
        DeleteAssetListener::execute($asset->createdBy(), $asset->asset);

        // Ensure removing the asset removes all associated data
        $this->assertEquals(0, Asset::count());
        $this->assertEquals(0, AssetTag::count());
        $this->assertEquals(0, AssetTagHash::count());
        $this->assertEquals(0, Attacker::count());
        $this->assertEquals(0, HiddenAlert::count());
        $this->assertEquals(0, Honeypot::count());
        $this->assertEquals(0, HoneypotEvent::count());
        $this->assertEquals(0, Port::count());
        $this->assertEquals(0, PortTag::count());
        $this->assertEquals(0, Scan::count());
        $this->assertEquals(0, Screenshot::count());
    }

    public function testItScansAnAsset(): void
    {
        // Mock external API calls
        $this->mockStartPortsScan();
        $this->mockGetPortsScanStatus();
        $this->mockGetPortsScanResult();
        $this->mockGetIpOwner();
        $this->mockGetIpGeoloc();
        $this->mockStartVulnsScanOnPort80();
        $this->mockStartVulnsScanOnPort443();
        $this->mockGetVulnsScanResultOnPort80();
        $this->mockGetVulnsScanResultOnPort443();
        $this->mockTranslate();

        // Setup the asset and trigger a scan
        $response = $this->addDns();

        /** @var Asset $asset */
        $asset = Asset::find($response['asset']['uid']);

        $response = $this->addTag($asset->id, 'demo');
        $response = $this->startMonitoringAsset(
            $asset->id,
            $asset->asset,
            $asset->tld(),
            $asset->type->value
        );

        // Check the output of the /assets/infos endpoint (1/2)
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->get("/api/adversary/infos-from-asset/" . base64_encode('www.example.com'));

        $response->assertStatus(200)->assertJson(function (AssertableJson $json) use ($asset) {
            $json->where('asset', 'www.example.com')
                ->whereType('modifications', 'array')
                ->where('modifications.0.asset_id', $asset->id)
                ->where('modifications.0.asset_name', $asset->asset)
                ->whereType('modifications.0.timestamp', 'string')
                ->where('modifications.0.user', "qa@computablefacts.com")
                ->where('tags', ['demo'])
                ->where('ports', [])
                ->where('vulnerabilities', [])
                ->where('timeline.nmap.id', null)
                ->where('timeline.nmap.start', null)
                ->where('timeline.nmap.end', null)
                ->where('timeline.sentinel.id', null)
                ->where('timeline.sentinel.start', null)
                ->where('timeline.sentinel.end', null)
                ->whereType('timeline.next_scan', 'string')
                ->where('hiddenAlerts', [])
                ->etc();
        });

        $job = (new TriggerScan())->withFakeQueueInteractions();
        $job->handle();

        $asset->refresh();

        // Check tables content
        $this->assertEquals(1, Asset::count());
        $this->assertEquals(1, AssetTag::count());
        $this->assertEquals(0, AssetTagHash::count());
        $this->assertEquals(0, Attacker::count());
        $this->assertEquals(0, HiddenAlert::count());
        $this->assertEquals(0, Honeypot::count());
        $this->assertEquals(0, HoneypotEvent::count());
        $this->assertEquals(2, Port::count());
        $this->assertEquals(20, PortTag::count());
        $this->assertEquals(2, Scan::count());
        $this->assertEquals(2, Screenshot::count());

        // Check the assets table
        $this->assertEquals('www.example.com', $asset->asset);
        $this->assertEquals(AssetTypesEnum::DNS, $asset->type);
        $this->assertEquals('example.com', $asset->tld());
        $this->assertNull($asset->discovery_id);
        $this->assertNull($asset->prev_scan_id);
        $this->assertEquals('6409ae68ed42e11e31e5f19d', $asset->cur_scan_id);
        $this->assertNull($asset->next_scan_id);
        $this->assertTrue($asset->is_monitored);
        $this->assertEquals($this->user->id, $asset->created_by);

        // Check the assets_tags table
        $this->assertEquals(['demo'], $asset->tags()->get()->pluck('tag')->toArray());

        // Check the ports table
        $ports = $asset->ports()->get();
        /** @var Port $port80 */
        $port80 = $ports->filter(fn(Port $port) => $port->port === 80)->first();
        /** @var Port $port443 */
        $port443 = $ports->filter(fn(Port $port) => $port->port === 443)->first();
        $screenshot80 = $port80->screenshot()->first();
        $screenshot443 = $port443->screenshot()->first();

        $this->assertEquals($port80->id, $screenshot80->port_id);
        $this->assertEquals($port443->id, $screenshot443->port_id);

        $this->assertEquals('www.example.com', $port80->hostname);
        $this->assertEquals('93.184.215.14', $port80->ip);
        $this->assertEquals(80, $port80->port);
        $this->assertEquals('tcp', $port80->protocol);
        $this->assertEquals('US', $port80->country);
        $this->assertEquals('http', $port80->service);
        $this->assertEquals('ECAcc (bsb|2789)', $port80->product);
        $this->assertFalse($port80->ssl);
        $this->assertEquals('EDGECAST, US', $port80->hosting_service_description);
        $this->assertEquals('ripencc', $port80->hosting_service_registry);
        $this->assertEquals('15133', $port80->hosting_service_asn);
        $this->assertEquals('93.184.215.0/24', $port80->hosting_service_cidr);
        $this->assertEquals('US', $port80->hosting_service_country_code);
        $this->assertEquals('2008-06-02', $port80->hosting_service_date);
        $this->assertFalse($port80->closed);

        $this->assertEquals('www.example.com', $port443->hostname);
        $this->assertEquals('93.184.215.14', $port443->ip);
        $this->assertEquals(443, $port443->port);
        $this->assertEquals('tcp', $port443->protocol);
        $this->assertEquals('US', $port443->country);
        $this->assertEquals('http', $port443->service);
        $this->assertEquals('ECAcc (bsb|2789)', $port443->product);
        $this->assertTrue($port443->ssl);
        $this->assertEquals('EDGECAST, US', $port443->hosting_service_description);
        $this->assertEquals('ripencc', $port443->hosting_service_registry);
        $this->assertEquals('15133', $port443->hosting_service_asn);
        $this->assertEquals('93.184.215.0/24', $port443->hosting_service_cidr);
        $this->assertEquals('US', $port443->hosting_service_country_code);
        $this->assertEquals('2008-06-02', $port443->hosting_service_date);
        $this->assertFalse($port443->closed);

        // Check the ports_tags table
        /** @var array $tags80 */
        $tags80 = $port80->tags()->orderBy('tag')->get()->pluck('tag')->toArray();
        /** @var array $tags443 */
        $tags443 = $port443->tags()->orderBy('tag')->get()->pluck('tag')->toArray();

        $this->assertEquals(['azure', 'azure cdn', 'demo', 'ecacc (bsb/27ab)', 'http', 'ssl-issuer|digicert inc', 'tls10', 'tls11', 'tls12', 'tls13'], $tags80);
        $this->assertEquals(['azure', 'azure cdn', 'demo', 'ecacc (bsb/27bf)', 'http', 'ssl-issuer|digicert inc', 'tls10', 'tls11', 'tls12', 'tls13'], $tags443);

        // Check the scans table
        $scans = $asset->scanCompleted();
        /** @var Scan $scan80 */
        $scan80 = $scans->filter(fn(Scan $scan) => $scan->vulns_scan_id === 'b9b5e877-bdfe-4b39-8c4b-8316e451730e')->first();
        /** @var Scan $scan443 */
        $scan443 = $scans->filter(fn(Scan $scan) => $scan->vulns_scan_id === 'a9a5d877-abed-4a39-8b4a-8316d451730d')->first();

        $this->assertEquals('6409ae68ed42e11e31e5f19d', $scan80->ports_scan_id);
        $this->assertEquals('b9b5e877-bdfe-4b39-8c4b-8316e451730e', $scan80->vulns_scan_id);
        $this->assertNotNull($scan80->ports_scan_begins_at);
        $this->assertNotNull($scan80->ports_scan_ends_at);
        $this->assertNotNull($scan80->vulns_scan_begins_at);
        $this->assertNotNull($scan80->vulns_scan_ends_at);
        $this->assertEquals($asset->id, $scan80->asset_id);

        $this->assertEquals('6409ae68ed42e11e31e5f19d', $scan443->ports_scan_id);
        $this->assertEquals('a9a5d877-abed-4a39-8b4a-8316d451730d', $scan443->vulns_scan_id);
        $this->assertNotNull($scan443->ports_scan_begins_at);
        $this->assertNotNull($scan443->ports_scan_ends_at);
        $this->assertNotNull($scan443->vulns_scan_begins_at);
        $this->assertNotNull($scan443->vulns_scan_ends_at);
        $this->assertEquals($asset->id, $scan443->asset_id);

        // Check the output of the /assets/infos endpoint (2/2)
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->get("/api/adversary/infos-from-asset/" . base64_encode('www.example.com'));

        $response->assertStatus(200)->assertJson(function (AssertableJson $json) use ($asset, $scan80, $scan443, $screenshot80, $screenshot443) {
            $json->where('asset', 'www.example.com')
                ->whereType('modifications', 'array')
                ->where('modifications.0.asset_id', $asset->id)
                ->where('modifications.0.asset_name', $asset->asset)
                ->whereType('modifications.0.timestamp', 'string')
                ->where('modifications.0.user', "qa@computablefacts.com")
                ->where('tags', ['demo'])
                ->whereType('ports', 'array')
                ->where('ports.0.ip', '93.184.215.14')
                ->where('ports.0.port', 80)
                ->where('ports.0.protocol', 'tcp')
                ->where('ports.0.products.0', 'ECAcc (bsb|2789)')
                ->where('ports.0.services.0', 'http')
                ->where('ports.0.tags', ['azure', 'azure cdn', 'demo', 'ecacc (bsb/27ab)', 'http', 'ssl-issuer|digicert inc', 'tls10', 'tls11', 'tls12', 'tls13'])
                ->where('ports.0.screenshotId', $screenshot80->id)
                ->where('ports.1.ip', '93.184.215.14')
                ->where('ports.1.port', 443)
                ->where('ports.1.protocol', 'tcp')
                ->where('ports.1.products.0', 'ECAcc (bsb|2789)')
                ->where('ports.1.services.0', 'http')
                ->where('ports.1.tags', ['azure', 'azure cdn', 'demo', 'ecacc (bsb/27bf)', 'http', 'ssl-issuer|digicert inc', 'tls10', 'tls11', 'tls12', 'tls13'])
                ->where('ports.1.screenshotId', $screenshot443->id)
                ->whereType('vulnerabilities', 'array')
                ->where('vulnerabilities.0.ip', '93.184.215.14')
                ->where('vulnerabilities.0.port', 80)
                ->where('vulnerabilities.0.protocol', 'tcp')
                ->where('vulnerabilities.0.type', 'weak_cipher_suites_v3_alert')
                ->where('vulnerabilities.0.tested', false)
                ->where('vulnerabilities.0.vulnerability', "A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href='http://www.example.com:443' target='_blank'>http://www.example.com:443</a></li></ul>")
                ->where('vulnerabilities.0.remediation', "Fix the vulnerability described in this alert")
                ->where('vulnerabilities.0.level', 'low')
                ->where('vulnerabilities.0.uid', 'a2a95bb1311b66abb394ac9015175dcd')
                ->where('vulnerabilities.0.cve_id', null)
                ->where('vulnerabilities.0.cve_cvss', null)
                ->where('vulnerabilities.0.cve_vendor', null)
                ->where('vulnerabilities.0.cve_product', null)
                ->where('vulnerabilities.0.title', "Weak Cipher Suites Detection")
                ->where('vulnerabilities.0.flarum_url', null)
                ->whereType('vulnerabilities.0.start_date', "string")
                ->where('vulnerabilities.0.is_hidden', false)
                ->where('vulnerabilities.1.ip', '93.184.215.14')
                ->where('vulnerabilities.1.port', 443)
                ->where('vulnerabilities.1.protocol', 'tcp')
                ->where('vulnerabilities.1.type', 'weak_cipher_suites_v3_alert')
                ->where('vulnerabilities.1.tested', false)
                ->where('vulnerabilities.1.vulnerability', "A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href='https://www.example.com:443' target='_blank'>https://www.example.com:443</a></li></ul>")
                ->where('vulnerabilities.1.remediation', "Fix the vulnerability described in this alert")
                ->where('vulnerabilities.1.level', 'low')
                ->where('vulnerabilities.1.uid', 'a2a95bb1311b66abb394ac9015175dcd')
                ->where('vulnerabilities.1.cve_id', null)
                ->where('vulnerabilities.1.cve_cvss', null)
                ->where('vulnerabilities.1.cve_vendor', null)
                ->where('vulnerabilities.1.cve_product', null)
                ->where('vulnerabilities.1.title', "Weak Cipher Suites Detection")
                ->where('vulnerabilities.1.flarum_url', null)
                ->whereType('vulnerabilities.1.start_date', "string")
                ->where('vulnerabilities.1.is_hidden', false)
                ->where('timeline.nmap.id', '6409ae68ed42e11e31e5f19d')
                ->whereType('timeline.nmap.start', "string")
                ->whereType('timeline.nmap.end', "string")
                ->where('timeline.sentinel.id', '000000000000000000000000')
                ->whereType('timeline.sentinel.start', "string")
                ->whereType('timeline.sentinel.end', "string")
                ->whereType('timeline.next_scan', "string")
                ->where('hiddenAlerts', [])
                ->etc();
        });

        // Check timeline
        /* $items = TimelineItem::fetchAlerts(null, null, null, 0, [
            [['asset_id', '=', $asset->id]],
        ]);

        $this->assertEquals(2, $items->count());

        $firstAttributes = $items->first()->attributes();
        $lastAttributes = $items->last()->attributes();

        $port80 = null;
        $port443 = null;

        if ($firstAttributes['port_number'] == 80) {
            $port80 = $firstAttributes;
            $port443 = $lastAttributes;
        } else {
            $port80 = $lastAttributes;
            $port443 = $firstAttributes;
        }

        // Port 80
        $this->assertEquals('www.example.com', $port80['asset_name']);
        $this->assertEquals('DNS', $port80['asset_type']);
        $this->assertEquals('example.com', $port80['asset_tld']);
        $this->assertEquals(json_encode(['demo']), $port80['asset_tags']);
        $this->assertEquals('93.184.215.14', $port80['asset_ip']);
        $this->assertEquals(80, $port80['port_number']);
        $this->assertEquals('tcp', $port80['port_protocol']);
        $this->assertEquals(json_encode(['azure', 'azure cdn', 'demo', 'ecacc (bsb/27ab)', 'http', 'ssl-issuer|digicert inc', 'tls10', 'tls11', 'tls12', 'tls13']), $port80['port_tags']);
        $this->assertEquals('http', $port80['port_service']);
        $this->assertEquals('ECAcc (bsb|2789)', $port80['port_product']);
        $this->assertEquals('EDGECAST, US', $port80['hosting_service_description']);
        $this->assertEquals('ripencc', $port80['hosting_service_registry']);
        $this->assertEquals('15133', $port80['hosting_service_asn']);
        $this->assertEquals('93.184.215.0/24', $port80['hosting_service_cidr']);
        $this->assertEquals('US', $port80['hosting_service_country_code']);
        $this->assertEquals('2008-06-02', $port80['hosting_service_date']);
        $this->assertEquals('weak_cipher_suites_v3_alert', $port80['vuln_type']);
        $this->assertEquals("A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href='http://www.example.com:443' target='_blank'>http://www.example.com:443</a></li></ul>", $port80['vuln_vulnerability_en']);
        $this->assertEquals("Un chiffrement faible est défini comme un algorithme de chiffrement/déchiffrement qui utilise une clé de longueur insuffisante. Utiliser une longueur insuffisante pour une clé dans un algorithme de chiffrement/déchiffrement ouvre la possibilité (ou la probabilité) que le schéma de chiffrement puisse être compromis.<br>L'URL suivante correspond à la vulnérabilité : <br><ul><li><a href='http://www.example.com:443' target='_blank'>http://www.example.com:443</a></li></ul>", $port80['vuln_vulnerability_fr']);
        $this->assertEquals("Fix the vulnerability described in this alert", $port80['vuln_remediation_en']);
        $this->assertEquals("Corrigez la vulnérabilité décrite dans cette alerte.", $port80['vuln_remediation_fr']);
        $this->assertEquals('Low', $port80['vuln_level']);
        $this->assertEquals('a2a95bb1311b66abb394ac9015175dcd', $port80['vuln_uid']);
        $this->assertFalse(isset($port80['vuln_cve_id']));
        $this->assertFalse(isset($port80['vuln_cve_cvss']));
        $this->assertFalse(isset($port80['vuln_cve_vendor']));
        $this->assertFalse(isset($port80['vuln_cve_product']));
        $this->assertEquals("Weak Cipher Suites Detection", $port80['vuln_title_en']);
        $this->assertEquals("Détection des suites de chiffrement faibles", $port80['vuln_title_fr']);
        $this->assertEquals('US', $port80['country']);
        $this->assertFalse(isset($port80['ssl']));

        // Port 443
        $this->assertEquals('www.example.com', $port443['asset_name']);
        $this->assertEquals('DNS', $port443['asset_type']);
        $this->assertEquals('example.com', $port443['asset_tld']);
        $this->assertEquals(json_encode(['demo']), $port443['asset_tags']);
        $this->assertEquals('93.184.215.14', $port443['asset_ip']);
        $this->assertEquals(443, $port443['port_number']);
        $this->assertEquals('tcp', $port443['port_protocol']);
        $this->assertEquals(json_encode(['azure', 'azure cdn', 'demo', 'ecacc (bsb/27bf)', 'http', 'ssl-issuer|digicert inc', 'tls10', 'tls11', 'tls12', 'tls13']), $port443['port_tags']);
        $this->assertEquals('http', $port443['port_service']);
        $this->assertEquals('ECAcc (bsb|2789)', $port443['port_product']);
        $this->assertEquals('EDGECAST, US', $port443['hosting_service_description']);
        $this->assertEquals('ripencc', $port443['hosting_service_registry']);
        $this->assertEquals('15133', $port443['hosting_service_asn']);
        $this->assertEquals('93.184.215.0/24', $port443['hosting_service_cidr']);
        $this->assertEquals('US', $port443['hosting_service_country_code']);
        $this->assertEquals('2008-06-02', $port443['hosting_service_date']);
        $this->assertEquals('weak_cipher_suites_v3_alert', $port443['vuln_type']);
        $this->assertEquals("A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href='https://www.example.com:443' target='_blank'>https://www.example.com:443</a></li></ul>", $port443['vuln_vulnerability_en']);
        $this->assertEquals("Un chiffrement faible est défini comme un algorithme de chiffrement/déchiffrement qui utilise une clé de longueur insuffisante. Utiliser une longueur insuffisante pour une clé dans un algorithme de chiffrement/déchiffrement ouvre la possibilité (ou la probabilité) que le schéma de chiffrement puisse être compromis.<br>L'URL suivante correspond à la vulnérabilité : <br><ul><li><a href='https://www.example.com:443' target='_blank'>https://www.example.com:443</a></li></ul>", $port443['vuln_vulnerability_fr']);
        $this->assertEquals("Fix the vulnerability described in this alert", $port443['vuln_remediation_en']);
        $this->assertEquals("Corrigez la vulnérabilité décrite dans cette alerte.", $port443['vuln_remediation_fr']);
        $this->assertEquals('Low', $port443['vuln_level']);
        $this->assertEquals('a2a95bb1311b66abb394ac9015175dcd', $port443['vuln_uid']);
        $this->assertFalse(isset($port443['vuln_cve_id']));
        $this->assertFalse(isset($port443['vuln_cve_cvss']));
        $this->assertFalse(isset($port443['vuln_cve_vendor']));
        $this->assertFalse(isset($port443['vuln_cve_product']));
        $this->assertEquals("Weak Cipher Suites Detection", $port443['vuln_title_en']);
        $this->assertEquals("Détection des suites de chiffrement faibles", $port443['vuln_title_fr']);
        $this->assertEquals('US', $port443['country']);
        $this->assertTrue($port443['ssl']); */

        // Remove the asset
        DeleteAssetListener::execute($asset->createdBy(), $asset->asset);

        // Ensure removing the asset removes all associated data
        $this->assertEquals(0, Asset::count());
        $this->assertEquals(0, AssetTag::count());
        $this->assertEquals(0, AssetTagHash::count());
        $this->assertEquals(0, Attacker::count());
        $this->assertEquals(0, HiddenAlert::count());
        $this->assertEquals(0, Honeypot::count());
        $this->assertEquals(0, HoneypotEvent::count());
        $this->assertEquals(0, Port::count());
        $this->assertEquals(0, PortTag::count());
        $this->assertEquals(0, Scan::count());
        $this->assertEquals(0, Screenshot::count());

        // Check timeline
        /* $items = TimelineItem::fetchAlerts(null, null, null, 0, [
            [['asset_id', '=', $asset->id]],
        ]);

        $this->assertEquals(0, $items->count()); */
    }

    private function addDns(): TestResponse
    {
        Log::info($this->token);
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->post("/api/inventory/assets", [
            'asset' => 'www.example.com',
            'watch' => false,
        ]);
        $response->assertStatus(200)->assertJson(function (AssertableJson $json) {
            $json->whereType('asset.uid', 'integer')
                ->whereType('asset.tags', 'array')
                ->where('asset.asset', 'www.example.com')
                ->where('asset.tld', 'example.com')
                ->where('asset.type', 'DNS')
                ->where('asset.status', 'invalid')
                ->where('asset.tags', [])
                ->etc();
        });
        return $response;
    }

    private function addIp(): TestResponse
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->post("/api/inventory/assets", [
            'asset' => '93.184.215.14',
            'watch' => false,
        ]);
        $response->assertStatus(200)->assertJson(function (AssertableJson $json) {
            $json->whereType('asset.uid', 'integer')
                ->whereType('asset.tags', 'array')
                ->where('asset.asset', '93.184.215.14')
                ->where('asset.tld', null)
                ->where('asset.type', 'IP')
                ->where('asset.status', 'invalid')
                ->where('asset.tags', [])
                ->etc();
        });
        return $response;
    }

    private function addRange(): TestResponse
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->post("/api/inventory/assets", [
            'asset' => '255.255.255.255/32',
            'watch' => false,
        ]);
        $response->assertStatus(200)->assertJson(function (AssertableJson $json) {
            $json->whereType('asset.uid', 'integer')
                ->whereType('asset.tags', 'array')
                ->where('asset.asset', '255.255.255.255/32')
                ->where('asset.tld', null)
                ->where('asset.type', 'RANGE')
                ->where('asset.status', 'invalid')
                ->where('asset.tags', [])
                ->etc();
        });
        return $response;
    }

    private function startMonitoringAsset(int $id, string $asset, ?string $tld, string $type): TestResponse
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->post("/api/inventory/asset/{$id}/monitoring/begin");
        $response->assertStatus(200)->assertJson(function (AssertableJson $json) use ($id, $asset, $tld, $type) {
            $json->whereType('asset.tags', 'array')
                ->where('asset.uid', $id)
                ->where('asset.asset', $asset)
                ->where('asset.tld', $tld)
                ->where('asset.type', $type)
                ->where('asset.status', 'valid')
                ->etc();
        });
        return $response;
    }

    private function stopMonitoringAsset(int $id, string $asset, ?string $tld, string $type): TestResponse
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->post("/api/inventory/asset/{$id}/monitoring/end");
        $response->assertStatus(200)->assertJson(function (AssertableJson $json) use ($id, $asset, $tld, $type) {
            $json->whereType('asset.tags', 'array')
                ->where('asset.uid', $id)
                ->where('asset.asset', $asset)
                ->where('asset.tld', $tld)
                ->where('asset.type', $type)
                ->where('asset.status', 'invalid')
                ->where('asset.tags', [])
                ->etc();
        });
        return $response;
    }

    private function addTag(int $assetId, string $tag): TestResponse
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->post("/api/facts/{$assetId}/metadata", [
            'key' => $tag,
        ]);
        $response->assertStatus(200)->assertJson(function (AssertableJson $json) use ($tag) {
            $json->whereType("0.id", "integer")
                ->where("0.key", $tag)
                ->etc();
        });
        return $response;
    }

    private function removeTag(int $assetId, int $tagId): TestResponse
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
        ])->delete("/api/facts/{$assetId}/metadata/{$tagId}");
        $response->assertStatus(200);
        return $response;
    }

    private function mockTranslate()
    {
        ApiUtils2::shouldReceive('translate')
            ->withAnyArgs()
            ->zeroOrMoreTimes()
            ->andReturnUsing(function (...$args) {
                if (count($args) > 0) {
                    if ($args[0] === "Weak Cipher Suites Detection" && $args[1] === 'fr') {
                        return [
                            'error' => false,
                            'response' => "Détection des suites de chiffrement faibles",
                        ];
                    }
                    if ($args[0] === "A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href='http://www.example.com:443' target='_blank'>http://www.example.com:443</a></li></ul>" && $args[1] === 'fr') {
                        return [
                            'error' => false,
                            'response' => "Un chiffrement faible est défini comme un algorithme de chiffrement/déchiffrement qui utilise une clé de longueur insuffisante. Utiliser une longueur insuffisante pour une clé dans un algorithme de chiffrement/déchiffrement ouvre la possibilité (ou la probabilité) que le schéma de chiffrement puisse être compromis.<br>L'URL suivante correspond à la vulnérabilité : <br><ul><li><a href='http://www.example.com:443' target='_blank'>http://www.example.com:443</a></li></ul>",
                        ];
                    }
                    if ($args[0] === "A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href='https://www.example.com:443' target='_blank'>https://www.example.com:443</a></li></ul>" && $args[1] === 'fr') {
                        return [
                            'error' => false,
                            'response' => "Un chiffrement faible est défini comme un algorithme de chiffrement/déchiffrement qui utilise une clé de longueur insuffisante. Utiliser une longueur insuffisante pour une clé dans un algorithme de chiffrement/déchiffrement ouvre la possibilité (ou la probabilité) que le schéma de chiffrement puisse être compromis.<br>L'URL suivante correspond à la vulnérabilité : <br><ul><li><a href='https://www.example.com:443' target='_blank'>https://www.example.com:443</a></li></ul>",
                        ];
                    }
                    if ($args[0] === "Fix the vulnerability described in this alert" && $args[1] === 'fr') {
                        return [
                            'error' => false,
                            'response' => "Corrigez la vulnérabilité décrite dans cette alerte.",
                        ];
                    }
                }
                return [
                    'error' => true,
                    'response' => null,
                ];
            });
    }

    private function mockStartPortsScan()
    {
        ApiUtils::shouldReceive('task_nmap_public')
            ->once()
            ->with('www.example.com')
            ->andReturn([
                'task_id' => '6409ae68ed42e11e31e5f19d',
            ]);
    }

    private function mockGetPortsScanStatus()
    {
        ApiUtils::shouldReceive('task_status_public')
            ->once()
            ->with('6409ae68ed42e11e31e5f19d')
            ->andReturn([
                'task_status' => 'SUCCESS',
            ]);
    }

    private function mockGetPortsScanStatusWhenScanEndsWithAnError()
    {
        ApiUtils::shouldReceive('task_status_public')
            ->once()
            ->with('6409ae68ed42e11e31e5f19d')
            ->andReturn([
                'task_status' => 'ERROR',
            ]);
    }

    private function mockGetPortsScanResult()
    {
        ApiUtils::shouldReceive('task_result_public')
            ->once()
            ->with('6409ae68ed42e11e31e5f19d')
            ->andReturn([
                'task_result' => [
                    [
                        'hostname' => 'www.example.com',
                        'ip' => '93.184.215.14',
                        'port' => 443,
                        'protocol' => 'tcp',
                    ], [
                        'hostname' => 'www.example.com',
                        'ip' => '93.184.215.14',
                        'port' => 80,
                        'protocol' => 'tcp',
                    ]
                ],
            ]);
    }

    private function mockGetIpGeoloc()
    {
        ApiUtils::shouldReceive('ip_geoloc_public')
            ->twice()
            ->with('93.184.215.14')
            ->andReturn([
                'data' => [
                    'country' => [
                        'iso_code' => 'US',
                    ],
                ],
            ]);
    }

    private function mockGetIpOwner()
    {
        ApiUtils::shouldReceive('ip_whois_public')
            ->twice()
            ->with('93.184.215.14')
            ->andReturn([
                'data' => [
                    'asn_description' => 'EDGECAST, US',
                    'asn_registry' => 'ripencc',
                    'asn' => '15133',
                    'asn_cidr' => '93.184.215.0/24',
                    'asn_country_code' => 'US',
                    'asn_date' => '2008-06-02',
                ],
            ]);
    }

    private function mockStartVulnsScanOnPort443()
    {
        ApiUtils::shouldReceive('task_start_scan_public')
            ->once()
            ->with('www.example.com', '93.184.215.14', 443, 'tcp', ['demo'])
            ->andReturn([
                'scan_id' => 'a9a5d877-abed-4a39-8b4a-8316d451730d',
            ]);
    }

    private function mockStartVulnsScanOnPort80()
    {
        ApiUtils::shouldReceive('task_start_scan_public')
            ->once()
            ->with('www.example.com', '93.184.215.14', 80, 'tcp', ['demo'])
            ->andReturn([
                'scan_id' => 'b9b5e877-bdfe-4b39-8c4b-8316e451730e',
            ]);
    }

    private function mockGetVulnsScanResultOnPort443()
    {
        ApiUtils::shouldReceive('task_get_scan_public')
            ->once()
            ->with('a9a5d877-abed-4a39-8b4a-8316d451730d')
            ->andReturn([
                'hostname' => 'www.example.com',
                'ip' => '93.184.215.14',
                'port' => 443,
                'protocol' => 'tcp',
                'client' => '',
                'cf_ui_data' => [],
                'tags' => [
                    'demo',
                    'Http',
                    'Azure',
                    'Azure Cdn',
                    'Ecacc (Bsb/27Bf)',
                    'Ssl-Issuer|Digicert Inc',
                    'Tls10',
                    'Tls11',
                    'Tls12',
                    'Tls13',
                ],
                'tests' => [],
                'scan_type' => 'port',
                'first_seen' => NULL,
                'last_seen' => NULL,
                'service' => 'http',
                'vendor' => '',
                'product' => 'ECAcc (bsb|2789)',
                'version' => '',
                'cpe' => 'cpe:2.3:a:ecacc:(bsb|2789):*:*:*:*:*:*:*:*',
                'ssl' => true,
                'current_task' => 'alerter',
                'current_task_status' => 'DONE',
                'current_task_id' => 'a9a5d877-abed-4a39-8b4a-8316d451730d',
                'current_task_ret' => '',
                'serviceConfidenceScore' => 1,
                'data' => [[
                    'fromCache' => false,
                    'cacheTimestamp' => '',
                    'commandExecuted' => '/tools/nuclei -duc -silent -exclude-tags token-spray,osint,misc,dos,fuzz,generic,wp-plugin,wordpress,xss -c 50 -target https://www.example.com -or -jle /tmp/tmppoysqnhe.sentinel',
                    'timestamp' => '2024-09-08T14:24:33.237209',
                    'tags' => [
                        'Ssl-Issuer|Digicert Inc',
                        'Tls10',
                        'Tls11',
                        'Tls12',
                        'Tls13',
                    ],
                    'alerts' => [],
                    'extractedInformation' => [],
                    'error' => '',
                    'rawOutput' => '[{"template": "dns/txt-fingerprint.yaml", "template-url": "https://templates.nuclei.sh/public/txt-fingerprint", "template-id": "txt-fingerprint", "template-path": "/root/nuclei-templates/dns/txt-fingerprint.yaml", "info": {"name": "DNS TXT Record Detected", "author": ["pdteam"], "tags": ["dns", "txt"], "description": "A DNS TXT record was detected. The TXT record lets a domain admin leave notes on a DNS server.", "reference": ["https://www.netspi.com/blog/technical/network-penetration-testing/analyzing-dns-txt-records-to-fingerprint-service-providers/"], "severity": "info", "metadata": {"max-request": 1}, "classification": {"cve-id": null, "cwe-id": ["cwe-200"]}}, "type": "dns", "host": "www.example.com.", "matched-at": "www.example.com", "extracted-results": ["\\"v=spf1 -all\\"", "\\"wgyf8z8cgvm2qmxpnbnldrcltvk4xqfn\\""], "timestamp": "2024-09-08T14:24:42.336393361Z", "matcher-status": true, "templateID": "txt-fingerprint", "matched": "www.example.com"}, {"template": "dns/dns-saas-service-detection.yaml", "template-url": "https://templates.nuclei.sh/public/dns-saas-service-detection", "template-id": "dns-saas-service-detection", "template-path": "/root/nuclei-templates/dns/dns-saas-service-detection.yaml", "info": {"name": "DNS SaaS Service Detection", "author": ["noah @thesubtlety", "pdteam"], "tags": ["dns", "service"], "description": "A CNAME DNS record was discovered", "reference": ["https://ns1.com/resources/cname", "https://www.theregister.com/2021/02/24/dns_cname_tracking/", "https://www.ionos.com/digitalguide/hosting/technical-matters/cname-record/"], "severity": "info", "metadata": {"max-request": 1}}, "type": "dns", "host": "www.example.com.", "matched-at": "www.example.com", "timestamp": "2024-09-08T14:24:42.339486446Z", "matcher-status": true, "templateID": "dns-saas-service-detection", "matched": "www.example.com"}, {"template": "ssl/deprecated-tls.yaml", "template-url": "https://templates.nuclei.sh/public/deprecated-tls", "template-id": "deprecated-tls", "template-path": "/root/nuclei-templates/ssl/deprecated-tls.yaml", "info": {"name": "Deprecated TLS Detection (TLS 1.1 or SSLv3)", "author": ["righettod", "forgedhallpass"], "tags": ["ssl"], "description": "Both TLS 1.1 and SSLv3 are deprecated in favor of stronger encryption.\\n", "reference": ["https://ssl-config.mozilla.org/#config=intermediate"], "severity": "info", "metadata": {"max-request": 3, "shodan-query": "ssl.version:sslv2 ssl.version:sslv3 ssl.version:tlsv1 ssl.version:tlsv1.1"}, "remediation": "Update the web server\'s TLS configuration to disable TLS 1.1 and SSLv3.\\n"}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls10"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:25:58.168161566Z", "matcher-status": true, "templateID": "deprecated-tls", "matched": "www.example.com:443"}, {"template": "ssl/deprecated-tls.yaml", "template-url": "https://templates.nuclei.sh/public/deprecated-tls", "template-id": "deprecated-tls", "template-path": "/root/nuclei-templates/ssl/deprecated-tls.yaml", "info": {"name": "Deprecated TLS Detection (TLS 1.1 or SSLv3)", "author": ["righettod", "forgedhallpass"], "tags": ["ssl"], "description": "Both TLS 1.1 and SSLv3 are deprecated in favor of stronger encryption.\\n", "reference": ["https://ssl-config.mozilla.org/#config=intermediate"], "severity": "info", "metadata": {"max-request": 3, "shodan-query": "ssl.version:sslv2 ssl.version:sslv3 ssl.version:tlsv1 ssl.version:tlsv1.1"}, "remediation": "Update the web server\'s TLS configuration to disable TLS 1.1 and SSLv3.\\n"}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls11"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:25:58.417212778Z", "matcher-status": true, "templateID": "deprecated-tls", "matched": "www.example.com:443"}, {"template": "ssl/ssl-dns-names.yaml", "template-url": "https://templates.nuclei.sh/public/ssl-dns-names", "template-id": "ssl-dns-names", "template-path": "/root/nuclei-templates/ssl/ssl-dns-names.yaml", "info": {"name": "SSL DNS Names", "author": ["pdteam"], "tags": ["ssl"], "description": "Extract the Subject Alternative Name (SAN) from the target\'s certificate. SAN facilitates the usage of additional hostnames with the same certificate.\\n", "severity": "info", "metadata": {"max-request": 1}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["example.com", "example.org", "www.example.com", "www.example.edu", "www.example.net", "www.example.org", "example.net", "example.edu"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:25:58.689868812Z", "matcher-status": true, "templateID": "ssl-dns-names", "matched": "www.example.com:443"}, {"template": "ssl/detect-ssl-issuer.yaml", "template-url": "https://templates.nuclei.sh/public/ssl-issuer", "template-id": "ssl-issuer", "template-path": "/root/nuclei-templates/ssl/detect-ssl-issuer.yaml", "info": {"name": "Detect SSL Certificate Issuer", "author": ["lingtren"], "tags": ["ssl"], "description": "Extract the issuer\'s organization from the target\'s certificate. Issuers are entities which sign and distribute certificates.\\n", "severity": "info", "metadata": {"max-request": 1}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["DigiCert Inc"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:25:58.690050431Z", "matcher-status": true, "templateID": "ssl-issuer", "matched": "www.example.com:443"}, {"template": "ssl/tls-version.yaml", "template-url": "https://templates.nuclei.sh/public/tls-version", "template-id": "tls-version", "template-path": "/root/nuclei-templates/ssl/tls-version.yaml", "info": {"name": "TLS Version - Detect", "author": ["pdteam", "pussycat0x"], "tags": ["ssl"], "description": "TLS version detection is a security process used to determine the version of the Transport Layer Security (TLS) protocol used by a computer or server.\\nIt is important to detect the TLS version in order to ensure secure communication between two computers or servers.\\n", "severity": "info", "metadata": {"max-request": 4}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls10"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:25:59.875119688Z", "matcher-status": true, "templateID": "tls-version", "matched": "www.example.com:443"}, {"template": "ssl/weak-cipher-suites.yaml", "template-url": "https://templates.nuclei.sh/public/weak-cipher-suites", "template-id": "weak-cipher-suites", "template-path": "/root/nuclei-templates/ssl/weak-cipher-suites.yaml", "info": {"name": "Weak Cipher Suites Detection", "author": ["pussycat0x"], "tags": ["ssl", "tls", "misconfig"], "description": "A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.", "reference": ["https://www.acunetix.com/vulnerabilities/web/tls-ssl-weak-cipher-suites/", "http://ciphersuite.info"], "severity": "low", "metadata": {"max-request": 4}}, "matcher-name": "tls-1.0", "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["[tls10 TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA]"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:25:59.94568698Z", "matcher-status": true, "templateID": "weak-cipher-suites", "matched": "www.example.com:443"}, {"template": "ssl/tls-version.yaml", "template-url": "https://templates.nuclei.sh/public/tls-version", "template-id": "tls-version", "template-path": "/root/nuclei-templates/ssl/tls-version.yaml", "info": {"name": "TLS Version - Detect", "author": ["pdteam", "pussycat0x"], "tags": ["ssl"], "description": "TLS version detection is a security process used to determine the version of the Transport Layer Security (TLS) protocol used by a computer or server.\\nIt is important to detect the TLS version in order to ensure secure communication between two computers or servers.\\n", "severity": "info", "metadata": {"max-request": 4}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls11"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:26:00.129024425Z", "matcher-status": true, "templateID": "tls-version", "matched": "www.example.com:443"}, {"template": "ssl/weak-cipher-suites.yaml", "template-url": "https://templates.nuclei.sh/public/weak-cipher-suites", "template-id": "weak-cipher-suites", "template-path": "/root/nuclei-templates/ssl/weak-cipher-suites.yaml", "info": {"name": "Weak Cipher Suites Detection", "author": ["pussycat0x"], "tags": ["ssl", "tls", "misconfig"], "description": "A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.", "reference": ["https://www.acunetix.com/vulnerabilities/web/tls-ssl-weak-cipher-suites/", "http://ciphersuite.info"], "severity": "low", "metadata": {"max-request": 4}}, "matcher-name": "tls-1.1", "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["[tls11 TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA]"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:26:00.194996425Z", "matcher-status": true, "templateID": "weak-cipher-suites", "matched": "www.example.com:443"}, {"template": "ssl/tls-version.yaml", "template-url": "https://templates.nuclei.sh/public/tls-version", "template-id": "tls-version", "template-path": "/root/nuclei-templates/ssl/tls-version.yaml", "info": {"name": "TLS Version - Detect", "author": ["pdteam", "pussycat0x"], "tags": ["ssl"], "description": "TLS version detection is a security process used to determine the version of the Transport Layer Security (TLS) protocol used by a computer or server.\\nIt is important to detect the TLS version in order to ensure secure communication between two computers or servers.\\n", "severity": "info", "metadata": {"max-request": 4}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls12"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:26:00.378538938Z", "matcher-status": true, "templateID": "tls-version", "matched": "www.example.com:443"}, {"template": "ssl/tls-version.yaml", "template-url": "https://templates.nuclei.sh/public/tls-version", "template-id": "tls-version", "template-path": "/root/nuclei-templates/ssl/tls-version.yaml", "info": {"name": "TLS Version - Detect", "author": ["pdteam", "pussycat0x"], "tags": ["ssl"], "description": "TLS version detection is a security process used to determine the version of the Transport Layer Security (TLS) protocol used by a computer or server.\\nIt is important to detect the TLS version in order to ensure secure communication between two computers or servers.\\n", "severity": "info", "metadata": {"max-request": 4}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls13"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:26:00.638605687Z", "matcher-status": true, "templateID": "tls-version", "matched": "www.example.com:443"}]',
                    'execDuration' => 242.30835723876953,
                    'summary' => 'Ran tool nuclei_scanner',
                    'tool' => 'nuclei_scanner',
                    'toolVersion' => '2.8.9',
                    'outputFormat' => 'raw',
                ], [
                    'fromCache' => false,
                    'cacheTimestamp' => '',
                    'commandExecuted' => '/usr/bin/timeout 120 /usr/bin/curl --silent \'http://splash:8050/render.json?url=https://www.example.com&png=1&timeout=90\' -o /tmp/tmpb67xfbca.sentinel',
                    'timestamp' => '2024-09-08T14:34:15.723800',
                    'tags' => [],
                    'alerts' => [],
                    'extractedInformation' => [],
                    'error' => '',
                    'rawOutput' => '{"png": "iVBORw0KGgoAAAANSUhEUgAABAAAAAMACAYAAAC6uhUNAAAACXBIWXMAAA9hAAAPYQGoP6dpAAAgAElEQVR4AezdCdxU0x/H8dO+KyUtitBmCZFo36VNmzYqskUlZSeJ7EuKSqHsKhIJ7YsWJSr9ZRey7yT7/j/fkzvu3GfmmXn2eZ77Oa/X08zcuev7nHub87vnnFto585d/xgSAggggAACCCCAAAIIIIAAAggUaIHCBfroODgEEEAAAQQQQAABBBBAAAEEEHACBAAoCAgggAACCCCAAAIIIIAAAgiEQIAAQAgymUNEAAEEEEAAAQQQQAABBBBAgAAAZQABBBBAAAEEEEAAAQQQQACBEAgQAAhBJnOICCCAAAIIIIAAAggggAACCBAAoAwggAACCCCAAAIIIIAAAgggEAIBAgAhyGQOEQEEEEAAAQQQQAABBBBAAAECAJQBBBBAAAEEEEAAAQQQQAABBEIgQAAgBJnMISKAAAIIIIAAAggggAACCCBAAIAygAACCCCAAAIIIIAAAggggEAIBAgAhCCTOUQEEEAAAQQQQAABBBBAAAEECABQBhBAAAEEEEAAAQQQQAABBBAIgQABgBBkMoeIAAIIIIAAAggggAACCCCAAAEAygACCCCAAAIIIIAAAggggAACIRAgABCCTOYQEUAAAQQQQAABBBBAAAEEECAAQBlAAAEEEEAAAQQQQAABBBBAIAQCBABCkMkcIgIIIIAAAggggAACCCCAAAIEACgDCCCAAAIIIIAAAggggAACCIRAgABACDKZQ0QAAQQQQAABBBBAAAEEEECAAABlAAEEEEAAAQQQQAABBBBAAIEQCBAACEEmc4gIIIAAAggggAACCCCAAAIIEACgDCCAAAIIIIAAAggggAACCCAQAgECACHIZA4RAQQQQAABBBBAAAEEEEAAAQIAlAEEEEAAAQQQQAABBBBAAAEEQiBAACAEmcwhIoAAAggggAACCCCAAAIIIEAAgDKAAAIIIIAAAggggAACCCCAQAgECACEIJM5RAQQQAABBBBAAAEEEEAAAQQIAFAGEEAAAQQQQAABBBBAAAEEEAiBAAGAEGQyh4gAAggggAACCCCAAAIIIIAAAQDKAAIIIIAAAggggAACCCCAAAIhECAAEIJM5hARQAABBBBAAAEEEEAAAQQQIABAGUAAAQQQQAABBBBAAAEEEEAgBAIEAEKQyRwiAggggAACCCCAAAIIIIAAAgQAKAMIIIAAAggggAACCCCAAAIIhECAAEAIMplDRAABBBBAAAEEEEAAAQQQQIAAAGUAAQQQQAABBBBAAAEEEEAAgRAIEAAIQSZziAgggAACCCCAAAIIIIAAAggQAKAMIIAAAggggAACCCCAAAIIIBACAQIAIchkDhEBBBBAAAEEEEAAAQQQQAABAgCUAQQQQAABBBBAAAEEEEAAAQRCIEAAIASZzCEigAACCCCAAAIIIIAAAgggQACAMoAAAggggAACCCCAAAIIIIBACAQIAIQgkzlEBBBAAAEEEEAAAQQQQAABBAgAUAYQQAABBBBAAAEEEEAAAQQQCIEAAYAQZDKHiAACCCCAAAIIIIAAAggggAABAMoAAggggAACCCCAAAIIIIAAAiEQIAAQgkzmEBFAAAEEEEAAAQQQQAABBBAgAEAZQAABBBBAAAEEEEAAAQQQQCAEAgQAQpDJHCICCCCAAAIIIIAAAggggAACBAAoAwgggAACCCCAAAIIIIAAAgiEQIAAQAgymUNEAAEEEEAAAQQQQAABBBBAgAAAZQABBBBAAAEEEEAAAQQQQACBEAgQAAhBJnOICCCAAAIIIIAAAggggAACCBAAoAwggAACCCCAAAIIIIAAAgggEAIBAgAhyGQOEQEEEEAAAQQQQAABBBBAAAECAJQBBBBAAAEEEEAAAQQQQAABBEIgQAAgBJnMISKAAAIIIIAAAggggAACCCBAAIAygAACCCCAAAIIIIAAAggggEAIBAgAhCCTOUQEEEAAAQQQQAABBBBAAAEECABQBhBAAAEEEEAAAQQQQAABBBAIgQABgBBkMoeIAAIIIIAAAggggAACCCCAAAEAygACCCCAAAIIIIAAAggggAACIRAgABCCTOYQEUAAAQQQQAABBBBAAAEEECAAQBlAAAEEEEAAAQQQQAABBBBAIAQCBABCkMkcIgIIIIAAAggggAACCCCAAAIEACgDCCCAAAIIIIAAAggggAACCIRAgABACDKZQ0QAAQQQQAABBBBAAAEEEECAAABlAAEEEEAAAQQQQAABBBBAAIEQCBAACEEmc4gIIIAAAggggAACCCCAAAIIEACgDCCAAAIIIIAAAggggAACCCAQAgECACHIZA4RAQQQQAABBBBAAAEEEEAAAQIAlAEEEEAAAQQQQAABBBBAAAEEQiBAACAEmcwhIoAAAggggAACCCCAAAIIIEAAgDKAAAIIIIAAAggggAACCCCAQAgECACEIJM5RAQQQAABBBBAAAEEEEAAAQQIAFAGEEAAAQQQQAABBBBAAAEEEAiBQNEQHCOHiEBE4J9//om85w0CCCCAAAIIIIAAAkGBQoUKBSfxGYECI0AAoMBkJQcST4BKfzwZpiOAAAIIIIAAAggEBfy/HQkGBHX4nN8FCADk9xxk/+MK+C/esWZK9H2sZZiGAAIIIIAAAgggUPAE4lX0vd+L8b4veBIcUUEXIABQ0HM4hMfnXah16EWKFDbFihW3r0VN4cKFDBfvEBYIDhkBBBBAAAEEEEhHQL8d//77H/PXX3+a33//3b7+5eb2/270fl/6p6WzSr5CIGUFCACkbNawY5kR8C7OWrZkyZKmePHimVkNyyCAAAIIIIAAAgiERECV+iJF9Ffc/XZUEOCXX34x3u9Kf6Vf0/yfQ0LEYRYgAZ4CUIAyM+yH4l2k5VCmTGkq/2EvEBw/AggggAACCCCQCQHdQCpTpkxkSf9vTE0Mfo7MyBsE8oEAAYB8kEnsYsYEdOdfTf5JCCCAAAIIIIAAAghkRqBo0aKmVKmSkco+lf7MKLJMKgoQAEjFXGGfMizgXZTV559m/xnmYwEEEEAAAQQQQACBgEDx4iXsTaUiMYMA3m/PwCJ8RCDlBQgApHwWsYMZEdCAfyQEEEAAAQQQQAABBLJDwLux5FX4vdfsWDfrQCAvBAgA5IU628xWAf+FmKb/2UrLyhBAAAEEEEAAgVALqCtAvOT/DRpvHqYjkGoCBABSLUfYn0wL6CKsR/2REEAAAQQQQAABBBDIDoHChQun6QJAxT87ZFlHXgkQAMgrebabIwI8liVHWFkpAggggAACCCAQSgF+W4Yy2wv0QRMAKNDZW/APjghswc9jjhABBBBAAAEEEMhrgXi/OeNNz+v9ZfsIxBMgABBPhukIIIAAAggggAACCCCAgE+ACr8Pg7f5UoAAQL7MNnYaAQQQQAABBBBAAAEEEEAAgYwJEADImBdzp6gA0dgUzRh2CwEEEEAAAQQQKIAC/PYsgJkakkMiABCSjOYwEUAAAQQQQAABBBBAAAEEwi1AACDc+c/RI4AAAggggAACCCCAAAIIhESAAEBIMprDRAABBBBAAAEEEEAAAQQQCLcAAYBw5z9HjwACCCCAAAIIIIAAAgggEBIBAgAhyWgOEwEEEEAAAQQQQAABBBBAINwCBADCnf8cPQIIIIAAAggggAACCCCAQEgECACEJKM5TAQQQAABBBBAAAEEEEAAgXALEAAId/5z9AgggAACCCCAAAIIIIAAAiERIAAQkozmMBFAAAEEEEAAAQQQQAABBMItQAAg3PnP0SOAAAIIIIAAAggggAACCIREgABASDKaw0QAAQQQQAABBBBAAAEEEAi3AAGAcOc/R48AAggggAACCCCAAAIIIBASAQIAIcloDhMBBBBAAAEEEEAAAQQQQCDcAgQAwp3/HD0CCCCAAAIIIIAAAggggEBIBAgAhCSjOUwEEEAAAQQQQAABBBBAAIFwCxAACHf+c/QIIIAAAggggAACCCCAAAIhESAAEJKM5jARQAABBBBAAAEEEEAAAQTCLUAAINz5z9EjgAACCCCAAAIIIIAAAgiERIAAQEgymsNEAAEEEEAAAQQQQAABBBAItwABgHDnP0ePAAIIIIAAAggggAACCCAQEgECACHJaA4TAQQQQAABBBBAAAEEEEAg3AIEAMKd/xw9AggggAACCCCAAAIIIIBASAQIAIQkozlMBBBAAAEEEEAAAQQQQACBcAsQAAh3/nP0CCCAAAIIIIAAAggggAACIREgABCSjOYwEUAAAQQQQAABBBBAAAEEwi1AACDc+c/RI4AAAggggAACCCCAAAIIhESAAEBIMprDRAABBBBAAAEEEEAAAQQQCLcAAYBw5z9HjwACCCCAAAIIIIAAAgggEBIBAgAhyWgOEwEEEEAAAQQQQAABBBBAINwCBADCnf8cPQIIIIAAAggggAACCCCAQEgECACEJKM5TAQQQAABBBBAAAEEEEAAgXALEAAId/5z9AgggAACCCCAAAIIIIAAAiERIAAQkozmMBFAAAEEEEAAAQQQQAABBMItQAAg3PnP0SOAAAIIIIAAAggggAACCIREgABASDKaw0QAAQQQQAABBBBAAAEEEAi3AAGAcOc/R48AAggggAACCCCAAAIIIBASAQIAIcloDhMBBBBAAAEEEEAAAQQQQCDcAgQAwp3/HD0CCCCAAAIIIIAAAggggEBIBAgAhCSjOUwEEEAAAQQQQAABBBBAAIFwCxAACHf+c/QIIIAAAggggAACCCCA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wACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgMrFm1IAABF5SURBVAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBP4BYNIDFE+DzWsAAAAASUVORK5CYII=", "url": "https://www.example.com/", "requestedUrl": "https://www.example.com/", "geometry": [0, 0, 1024, 768], "title": "Example Domain"}',
                    'execDuration' => 0.08572983741760254,
                    'summary' => 'Ran tool splash',
                    'tool' => 'splash',
                    'toolVersion' => '3.5',
                    'outputFormat' => 'json',
                ], [
                    "tool" => "alerter",
                    "rawOutput" => json_encode([
                            "type" => "weak_cipher_suites_v3_alert",
                            "values" => [
                                "www.example.com",
                                '93.184.215.14',
                                443,
                                "tcp",
                                'A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href=\'https://www.example.com:443\' target=\'_blank\'>https://www.example.com:443</a></li></ul>',
                                'Fix the vulnerability described in this alert',
                                "Low",
                                "a2a95bb1311b66abb394ac9015175dcd",
                                "",
                                "",
                                "",
                                "",
                                "Weak Cipher Suites Detection"
                            ],
                        ]) . "\n" . json_encode([
                            "type" => "weak_cipher_suites_v3_alert",
                            "values" => [
                                "www.example.com",
                                '93.184.215.14',
                                443,
                                "tcp",
                                'A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href=\'https://www.example.com:443\' target=\'_blank\'>https://www.example.com:443</a></li></ul>',
                                'Fix the vulnerability described in this alert',
                                "Low",
                                "a2a95bb1311b66abb394ac9015175dcd",
                                "",
                                "",
                                "",
                                "",
                                "Weak Cipher Suites Detection"
                            ],
                        ]),
                ]],
                'scan_id' => 'a9a5d877-abed-4a39-8b4a-8316d451730d',
            ]);
    }

    private function mockGetVulnsScanResultOnPort80()
    {
        ApiUtils::shouldReceive('task_get_scan_public')
            ->once()
            ->with('b9b5e877-bdfe-4b39-8c4b-8316e451730e')
            ->andReturn([
                "hostname" => "www.example.com",
                "ip" => "93.184.215.14",
                "port" => 80,
                "protocol" => "tcp",
                "client" => "",
                "cf_ui_data" => [],
                "tags" => [
                    "demo",
                    "Http",
                    "Azure",
                    "Azure Cdn",
                    "Ecacc (Bsb/27Ab)",
                    "Tls10",
                    "Tls11",
                    "Tls12",
                    "Tls13",
                    "Ssl-Issuer|Digicert Inc"
                ],
                "tests" => [],
                "scan_type" => "port",
                "first_seen" => null,
                "last_seen" => null,
                "service" => "http",
                "vendor" => "",
                "product" => "ECAcc (bsb|2789)",
                "version" => "",
                "cpe" => "cpe:2.3:a:ecacc:(bsb|2789):*:*:*:*:*:*:*:*",
                "ssl" => false,
                "current_task" => "alerter",
                "current_task_status" => "DONE",
                "current_task_id" => "b9b5e877-bdfe-4b39-8c4b-8316e451730e",
                "current_task_ret" => "",
                "serviceConfidenceScore" => 1,
                "data" => [[
                    'fromCache' => false,
                    'cacheTimestamp' => '',
                    'commandExecuted' => '/tools/nuclei -duc -silent -exclude-tags token-spray,osint,misc,dos,fuzz,generic,wp-plugin,wordpress,xss -c 50 -target http://www.example.com -or -jle /tmp/tmps4607zwq.sentinel',
                    'timestamp' => '2024-09-08T14:23:32.722427',
                    'tags' => [
                        'Tls10',
                        'Tls11',
                        'Tls12',
                        'Tls13',
                        'Ssl-Issuer|Digicert Inc',
                    ],
                    'alerts' => [[
                        'asset' => 'www.example.com',
                        'port' => 80,
                        'protocol' => 'tcp',
                        'tool' => 'nuclei_scanner',
                        'type' => 'weak_cipher_suites',
                        'title' => 'Weak Cipher Suites Detection',
                        'level' => 'Low',
                        'vulnerability' => 'A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href=\'http://www.example.com:443\' target=\'_blank\'>http://www.example.com:443</a></li></ul>',
                        'remediation' => 'Fix the vulnerability described in this alert',
                        'cve_id' => '',
                        'cve_cvss' => '',
                        'cve_vendor' => '',
                        'cve_product' => '',
                        'uid' => 'a2a95bb1311b66abb394ac9015175dcd',
                    ], [
                        'asset' => 'www.example.com',
                        'port' => 80,
                        'protocol' => 'tcp',
                        'tool' => 'nuclei_scanner',
                        'type' => 'weak_cipher_suites',
                        'title' => 'Weak Cipher Suites Detection',
                        'level' => 'Low',
                        'vulnerability' => 'A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.<br>The following URL matched the vulnerability: <br><ul><li><a href=\'http://www.example.com:443\' target=\'_blank\'>http://www.example.com:443</a></li></ul>',
                        'remediation' => 'Fix the vulnerability described in this alert',
                        'cve_id' => '',
                        'cve_cvss' => '',
                        'cve_vendor' => '',
                        'cve_product' => '',
                        'uid' => 'a2a95bb1311b66abb394ac9015175dcd',
                    ]],
                    'extractedInformation' => [],
                    'error' => '',
                    'rawOutput' => '[{"template": "dns/txt-fingerprint.yaml", "template-url": "https://templates.nuclei.sh/public/txt-fingerprint", "template-id": "txt-fingerprint", "template-path": "/root/nuclei-templates/dns/txt-fingerprint.yaml", "info": {"name": "DNS TXT Record Detected", "author": ["pdteam"], "tags": ["dns", "txt"], "description": "A DNS TXT record was detected. The TXT record lets a domain admin leave notes on a DNS server.", "reference": ["https://www.netspi.com/blog/technical/network-penetration-testing/analyzing-dns-txt-records-to-fingerprint-service-providers/"], "severity": "info", "metadata": {"max-request": 1}, "classification": {"cve-id": null, "cwe-id": ["cwe-200"]}}, "type": "dns", "host": "www.example.com.", "matched-at": "www.example.com", "extracted-results": ["\\"v=spf1 -all\\"", "\\"wgyf8z8cgvm2qmxpnbnldrcltvk4xqfn\\""], "timestamp": "2024-09-08T14:23:41.949330745Z", "matcher-status": true, "templateID": "txt-fingerprint", "matched": "www.example.com"}, {"template": "dns/dns-saas-service-detection.yaml", "template-url": "https://templates.nuclei.sh/public/dns-saas-service-detection", "template-id": "dns-saas-service-detection", "template-path": "/root/nuclei-templates/dns/dns-saas-service-detection.yaml", "info": {"name": "DNS SaaS Service Detection", "author": ["noah @thesubtlety", "pdteam"], "tags": ["dns", "service"], "description": "A CNAME DNS record was discovered", "reference": ["https://ns1.com/resources/cname", "https://www.theregister.com/2021/02/24/dns_cname_tracking/", "https://www.ionos.com/digitalguide/hosting/technical-matters/cname-record/"], "severity": "info", "metadata": {"max-request": 1}}, "type": "dns", "host": "www.example.com.", "matched-at": "www.example.com", "timestamp": "2024-09-08T14:23:41.957427882Z", "matcher-status": true, "templateID": "dns-saas-service-detection", "matched": "www.example.com"}, {"template": "ssl/deprecated-tls.yaml", "template-url": "https://templates.nuclei.sh/public/deprecated-tls", "template-id": "deprecated-tls", "template-path": "/root/nuclei-templates/ssl/deprecated-tls.yaml", "info": {"name": "Deprecated TLS Detection (TLS 1.1 or SSLv3)", "author": ["righettod", "forgedhallpass"], "tags": ["ssl"], "description": "Both TLS 1.1 and SSLv3 are deprecated in favor of stronger encryption.\\n", "reference": ["https://ssl-config.mozilla.org/#config=intermediate"], "severity": "info", "metadata": {"max-request": 3, "shodan-query": "ssl.version:sslv2 ssl.version:sslv3 ssl.version:tlsv1 ssl.version:tlsv1.1"}, "remediation": "Update the web server\'s TLS configuration to disable TLS 1.1 and SSLv3.\\n"}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls10"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:24:46.302621165Z", "matcher-status": true, "templateID": "deprecated-tls", "matched": "www.example.com:443"}, {"template": "ssl/deprecated-tls.yaml", "template-url": "https://templates.nuclei.sh/public/deprecated-tls", "template-id": "deprecated-tls", "template-path": "/root/nuclei-templates/ssl/deprecated-tls.yaml", "info": {"name": "Deprecated TLS Detection (TLS 1.1 or SSLv3)", "author": ["righettod", "forgedhallpass"], "tags": ["ssl"], "description": "Both TLS 1.1 and SSLv3 are deprecated in favor of stronger encryption.\\n", "reference": ["https://ssl-config.mozilla.org/#config=intermediate"], "severity": "info", "metadata": {"max-request": 3, "shodan-query": "ssl.version:sslv2 ssl.version:sslv3 ssl.version:tlsv1 ssl.version:tlsv1.1"}, "remediation": "Update the web server\'s TLS configuration to disable TLS 1.1 and SSLv3.\\n"}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls11"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:24:46.555748564Z", "matcher-status": true, "templateID": "deprecated-tls", "matched": "www.example.com:443"}, {"template": "ssl/tls-version.yaml", "template-url": "https://templates.nuclei.sh/public/tls-version", "template-id": "tls-version", "template-path": "/root/nuclei-templates/ssl/tls-version.yaml", "info": {"name": "TLS Version - Detect", "author": ["pdteam", "pussycat0x"], "tags": ["ssl"], "description": "TLS version detection is a security process used to determine the version of the Transport Layer Security (TLS) protocol used by a computer or server.\\nIt is important to detect the TLS version in order to ensure secure communication between two computers or servers.\\n", "severity": "info", "metadata": {"max-request": 4}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls10"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:24:46.807721555Z", "matcher-status": true, "templateID": "tls-version", "matched": "www.example.com:443"}, {"template": "ssl/tls-version.yaml", "template-url": "https://templates.nuclei.sh/public/tls-version", "template-id": "tls-version", "template-path": "/root/nuclei-templates/ssl/tls-version.yaml", "info": {"name": "TLS Version - Detect", "author": ["pdteam", "pussycat0x"], "tags": ["ssl"], "description": "TLS version detection is a security process used to determine the version of the Transport Layer Security (TLS) protocol used by a computer or server.\\nIt is important to detect the TLS version in order to ensure secure communication between two computers or servers.\\n", "severity": "info", "metadata": {"max-request": 4}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls11"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:24:47.059203966Z", "matcher-status": true, "templateID": "tls-version", "matched": "www.example.com:443"}, {"template": "ssl/tls-version.yaml", "template-url": "https://templates.nuclei.sh/public/tls-version", "template-id": "tls-version", "template-path": "/root/nuclei-templates/ssl/tls-version.yaml", "info": {"name": "TLS Version - Detect", "author": ["pdteam", "pussycat0x"], "tags": ["ssl"], "description": "TLS version detection is a security process used to determine the version of the Transport Layer Security (TLS) protocol used by a computer or server.\\nIt is important to detect the TLS version in order to ensure secure communication between two computers or servers.\\n", "severity": "info", "metadata": {"max-request": 4}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls12"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:24:47.312604222Z", "matcher-status": true, "templateID": "tls-version", "matched": "www.example.com:443"}, {"template": "ssl/tls-version.yaml", "template-url": "https://templates.nuclei.sh/public/tls-version", "template-id": "tls-version", "template-path": "/root/nuclei-templates/ssl/tls-version.yaml", "info": {"name": "TLS Version - Detect", "author": ["pdteam", "pussycat0x"], "tags": ["ssl"], "description": "TLS version detection is a security process used to determine the version of the Transport Layer Security (TLS) protocol used by a computer or server.\\nIt is important to detect the TLS version in order to ensure secure communication between two computers or servers.\\n", "severity": "info", "metadata": {"max-request": 4}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["tls13"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:24:47.561466125Z", "matcher-status": true, "templateID": "tls-version", "matched": "www.example.com:443"}, {"template": "ssl/weak-cipher-suites.yaml", "template-url": "https://templates.nuclei.sh/public/weak-cipher-suites", "template-id": "weak-cipher-suites", "template-path": "/root/nuclei-templates/ssl/weak-cipher-suites.yaml", "info": {"name": "Weak Cipher Suites Detection", "author": ["pussycat0x"], "tags": ["ssl", "tls", "misconfig"], "description": "A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.", "reference": ["https://www.acunetix.com/vulnerabilities/web/tls-ssl-weak-cipher-suites/", "http://ciphersuite.info"], "severity": "low", "metadata": {"max-request": 4}}, "matcher-name": "tls-1.0", "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["[tls10 TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA]"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:24:47.811059302Z", "matcher-status": true, "templateID": "weak-cipher-suites", "matched": "www.example.com:443"}, {"template": "ssl/ssl-dns-names.yaml", "template-url": "https://templates.nuclei.sh/public/ssl-dns-names", "template-id": "ssl-dns-names", "template-path": "/root/nuclei-templates/ssl/ssl-dns-names.yaml", "info": {"name": "SSL DNS Names", "author": ["pdteam"], "tags": ["ssl"], "description": "Extract the Subject Alternative Name (SAN) from the target\'s certificate. SAN facilitates the usage of additional hostnames with the same certificate.\\n", "severity": "info", "metadata": {"max-request": 1}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["example.com", "example.org", "www.example.com", "www.example.edu", "www.example.net", "www.example.org", "example.net", "example.edu"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:24:47.95483624Z", "matcher-status": true, "templateID": "ssl-dns-names", "matched": "www.example.com:443"}, {"template": "ssl/detect-ssl-issuer.yaml", "template-url": "https://templates.nuclei.sh/public/ssl-issuer", "template-id": "ssl-issuer", "template-path": "/root/nuclei-templates/ssl/detect-ssl-issuer.yaml", "info": {"name": "Detect SSL Certificate Issuer", "author": ["lingtren"], "tags": ["ssl"], "description": "Extract the issuer\'s organization from the target\'s certificate. Issuers are entities which sign and distribute certificates.\\n", "severity": "info", "metadata": {"max-request": 1}}, "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["DigiCert Inc"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:24:47.954991234Z", "matcher-status": true, "templateID": "ssl-issuer", "matched": "www.example.com:443"}, {"template": "ssl/weak-cipher-suites.yaml", "template-url": "https://templates.nuclei.sh/public/weak-cipher-suites", "template-id": "weak-cipher-suites", "template-path": "/root/nuclei-templates/ssl/weak-cipher-suites.yaml", "info": {"name": "Weak Cipher Suites Detection", "author": ["pussycat0x"], "tags": ["ssl", "tls", "misconfig"], "description": "A weak cipher is defined as an encryption/decryption algorithm that uses a key of insufficient length. Using an insufficient length for a key in an encryption/decryption algorithm opens up the possibility (or probability) that the encryption scheme could be broken.", "reference": ["https://www.acunetix.com/vulnerabilities/web/tls-ssl-weak-cipher-suites/", "http://ciphersuite.info"], "severity": "low", "metadata": {"max-request": 4}}, "matcher-name": "tls-1.1", "type": "ssl", "host": "www.example.com", "matched-at": "www.example.com:443", "extracted-results": ["[tls11 TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA]"], "ip": "93.184.215.14", "timestamp": "2024-09-08T14:24:48.058490964Z", "matcher-status": true, "templateID": "weak-cipher-suites", "matched": "www.example.com:443"}]',
                    'execDuration' => 137.43784737586975,
                    'summary' => 'Ran tool nuclei_scanner',
                    'tool' => 'nuclei_scanner',
                    'toolVersion' => '2.8.9',
                    'outputFormat' => 'raw',
                ], [
                    'fromCache' => false,
                    'cacheTimestamp' => '',
                    'commandExecuted' => '/usr/bin/timeout 120 /usr/bin/curl --silent \'http://splash:8050/render.json?url=http://www.example.com&png=1&timeout=90\' -o /tmp/tmp93ujh1b5.sentinel',
                    'timestamp' => '2024-09-08T14:32:10.949310',
                    'tags' => [],
                    'alerts' => [],
                    'extractedInformation' => [],
                    'error' => '',
                    'rawOutput' => '{"png": "iVBORw0KGgoAAAANSUhEUgAABAAAAAMACAYAAAC6uhUNAAAACXBIWXMAAA9hAAAPYQGoP6dpAAAgAElEQVR4AezdCdxU0x/H8dO+KyUtitBmCZFo36VNmzYqskUlZSeJ7EuKSqHsKhIJ7YsWJSr9ZRey7yT7/j/fkzvu3GfmmXn2eZ77Oa/X08zcuev7nHub87vnnFto585d/xgSAggggAACCCCAAAIIIIAAAggUaIHCBfroODgEEEAAAQQQQAABBBBAAAEEEHACBAAoCAgggAACCCCAAAIIIIAAAgiEQIAAQAgymUNEAAEEEEAAAQQQQAABBBBAgAAAZQABBBBAAAEEEEAAAQQQQACBEAgQAAhBJnOICCCAAAIIIIAAAggggAACCBAAoAwggAACCCCAAAIIIIAAAgggEAIBAgAhyGQOEQEEEEAAAQQQQAABBBBAAAECAJQBBBBAAAEEEEAAAQQQQAABBEIgQAAgBJnMISKAAAIIIIAAAggggAACCCBAAIAygAACCCCAAAIIIIAAAggggEAIBAgAhCCTOUQEEEAAAQQQQAABBBBAAAEECABQBhBAAAEEEEAAAQQQQAABBBAIgQABgBBkMoeIAAIIIIAAAggggAACCCCAAAEAygACCCCAAAIIIIAAAggggAACIRAgABCCTOYQEUAAAQQQQAABBBBAAAEEECAAQBlAAAEEEEAAAQQQQAABBBBAIAQCBABCkMkcIgIIIIAAAggggAACCCCAAAIEACgDCCCAAAIIIIAAAggggAACCIRAgABACDKZQ0QAAQQQQAABBBBAAAEEEECAAABlAAEEEEAAAQQQQAABBBBAAIEQCBAACEEmc4gIIIAAAggggAACCCCAAAIIEACgDCCAAAIIIIAAAggggAACCCAQAgECACHIZA4RAQQQQAABBBBAAAEEEEAAAQIAlAEEEEAAAQQQQAABBBBAAAEEQiBAACAEmcwhIoAAAggggAACCCCAAAIIIEAAgDKAAAIIIIAAAggggAACCCCAQAgECACEIJM5RAQQQAABBBBAAAEEEEAAAQQIAFAGEEAAAQQQQAABBBBAAAEEEAiBAAGAEGQyh4gAAggggAACCCCAAAIIIIAAAQDKAAIIIIAAAggggAACCCCAAAIhECAAEIJM5hARQAABBBBAAAEEEEAAAQQQIABAGUAAAQQQQAABBBBAAAEEEEAgBAIEAEKQyRwiAggggAACCCCAAAIIIIAAAgQAKAMIIIAAAggggAACCCCAAAIIhECAAEAIMplDRAABBBBAAAEEEEAAAQQQQIAAAGUAAQQQQAABBBBAAAEEEEAAgRAIEAAIQSZziAgggAACCCCAAAIIIIAAAggQAKAMIIAAAggggAACCCCAAAIIIBACAQIAIchkDhEBBBBAAAEEEEAAAQQQQAABAgCUAQQQQAABBBBAAAEEEEAAAQRCIEAAIASZzCEigAACCCCAAAIIIIAAAgggQACAMoAAAggggAACCCCAAAIIIIBACAQIAIQgkzlEBBBAAAEEEEAAAQQQQAABBAgAUAYQQAABBBBAAAEEEEAAAQQQCIEAAYAQZDKHiAACCCCAAAIIIIAAAggggAABAMoAAggggAACCCCAAAIIIIAAAiEQIAAQgkzmEBFAAAEEEEAAAQQQQAABBBAgAEAZQAABBBBAAAEEEEAAAQQQQCAEAgQAQpDJHCICCCCAAAIIIIAAAggggAACBAAoAwgggAACCCCAAAIIIIAAAgiEQIAAQAgymUNEAAEEEEAAAQQQQAABBBBAgAAAZQABBBBAAAEEEEAAAQQQQACBEAgQAAhBJnOICCCAAAIIIIAAAggggAACCBAAoAwggAACCCCAAAIIIIAAAgggEAIBAgAhyGQOEQEEEEAAAQQQQAABBBBAAAECAJQBBBBAAAEEEEAAAQQQQAABBEIgQAAgBJnMISKAAAIIIIAAAggggAACCCBAAIAygAACCCCAAAIIIIAAAggggEAIBAgAhCCTOUQEEEAAAQQQQAABBBBAAAEECABQBhBAAAEEEEAAAQQQQAABBBAIgQABgBBkMoeIAAIIIIAAAggggAACCCCAAAEAygACCCCAAAIIIIAAAggggAACIRAgABCCTOYQEUAAAQQQQAABBBBAAAEEECAAQBlAAAEEEEAAAQQQQAABBBBAIAQCBABCkMkcIgIIIIAAAggggAACCCCAAAIEACgDCCCAAAIIIIAAAggggAACCIRAgABACDKZQ0QAAQQQQAABBBBAAAEEEECAAABlAAEEEEAAAQQQQAABBBBAAIEQCBAACEEmc4gIIIAAAggggAACCCCAAAIIEACgDCCAAAIIIIAAAggggAACCCAQAgECACHIZA4RAQQQQAABBBBAAAEEEEAAAQIAlAEEEEAAAQQQQAABBBBAAAEEQiBAACAEmcwhIoAAAggggAACCCCAAAIIIEAAgDKAAAIIIIAAAggggAACCCCAQAgECACEIJM5RAQQQAABBBBAAAEEEEAAAQQIAFAGEEAAAQQQQAABBBBAAAEEEAiBQNEQHCOHiEBE4J9//om85w0CCCCAAAIIIIAAAkGBQoUKBSfxGYECI0AAoMBkJQcST4BKfzwZpiOAAAIIIIAAAggEBfy/HQkGBHX4nN8FCADk9xxk/+MK+C/esWZK9H2sZZiGAAIIIIAAAgggUPAE4lX0vd+L8b4veBIcUUEXIABQ0HM4hMfnXah16EWKFDbFihW3r0VN4cKFDBfvEBYIDhkBBBBAAAEEEEhHQL8d//77H/PXX3+a33//3b7+5eb2/270fl/6p6WzSr5CIGUFCACkbNawY5kR8C7OWrZkyZKmePHimVkNyyCAAAIIIIAAAgiERECV+iJF9Ffc/XZUEOCXX34x3u9Kf6Vf0/yfQ0LEYRYgAZ4CUIAyM+yH4l2k5VCmTGkq/2EvEBw/AggggAACCCCQCQHdQCpTpkxkSf9vTE0Mfo7MyBsE8oEAAYB8kEnsYsYEdOdfTf5JCCCAAAIIIIAAAghkRqBo0aKmVKmSkco+lf7MKLJMKgoQAEjFXGGfMizgXZTV559m/xnmYwEEEEAAAQQQQACBgEDx4iXsTaUiMYMA3m/PwCJ8RCDlBQgApHwWsYMZEdCAfyQEEEAAAQQQQAABBLJDwLux5FX4vdfsWDfrQCAvBAgA5IU628xWAf+FmKb/2UrLyhBAAAEEEEAAgVALqCtAvOT/DRpvHqYjkGoCBABSLUfYn0wL6CKsR/2REEAAAQQQQAABBBDIDoHChQun6QJAxT87ZFlHXgkQAMgrebabIwI8liVHWFkpAggggAACCCAQSgF+W4Yy2wv0QRMAKNDZW/APjghswc9jjhABBBBAAAEEEMhrgXi/OeNNz+v9ZfsIxBMgABBPhukIIIAAAggggAACCCCAgE+ACr8Pg7f5UoAAQL7MNnYaAQQQQAABBBBAAAEEEEAAgYwJEADImBdzp6gA0dgUzRh2CwEEEEAAAQQQKIAC/PYsgJkakkMiABCSjOYwEUAAAQQQQAABBBBAAAEEwi1AACDc+c/RI4AAAggggAACCCCAAAIIhESAAEBIMprDRAABBBBAAAEEEEAAAQQQCLcAAYBw5z9HjwACCCCAAAIIIIAAAgggEBIBAgAhyWgOEwEEEEAAAQQQQAABBBBAINwCBADCnf8cPQIIIIAAAggggAACCCCAQEgECACEJKM5TAQQQAABBBBAAAEEEEAAgXALEAAId/5z9AgggAACCCCAAAIIIIAAAiERIAAQkozmMBFAAAEEEEAAAQQQQAABBMItQAAg3PnP0SOAAAIIIIAAAggggAACCIREgABASDKaw0QAAQQQQAABBBBAAAEEEAi3AAGAcOc/R48AAggggAACCCCAAAIIIBASAQIAIcloDhMBBBBAAAEEEEAAAQQQQCDcAgQAwp3/HD0CCCCAAAIIIIAAAggggEBIBAgAhCSjOUwEEEAAAQQQQAABBBBAAIFwCxAACHf+c/QIIIAAAggggAACCCCAAAIhESAAEJKM5jARQAABBBBAAAEEEEAAAQTCLUAAINz5z9EjgAACCCCAAAIIIIAAAgiERIAAQEgymsNEAAEEEEAAAQQQQAABBBAItwABgHDnP0ePAAIIIIAAAggggAACCCAQEgECACHJaA4TAQQQQAABBBBAAAEEEEAg3AIEAMKd/xw9AggggAACCCCAAAIIIIBASAQIAIQkozlMBBBAAAEEEEAAAQQQQACBcAsQAAh3/nP0CCCAAAIIIIAAAggggAACIREgABCSjOYwEUAAAQQQQAABBBBAAAEEwi1AACDc+c/RI4AAAggggAACCCCAAAIIhESAAEBIMprDRAABBBBAAAEEEEAAAQQQCLcAAYBw5z9HjwACCCCAAAIIIIAAAgggEBIBAgAhyWgOEwEEEEAAAQQQQAABBBBAINwCBADCnf8cPQIIIIAAAggggAACCCCAQEgECACEJKM5TAQQQAABBBBAAAEEEEAAgXALEAAId/5z9AgggAACCCCAAAIIIIAAAiERIAAQkozmMBFAAAEEEEAAAQQQQAABBMItQAAg3PnP0SOAAAIIIIAAAggggAACCIREgABASDKaw0QAAQQQQAABBBBAAAEEEAi3AAGAcOc/R48AAggggAACCCCAAAIIIBASAQIAIcloDhMBBBBAAAEEEEAAAQQQQCDcAgQAwp3/HD0CCCCAAAIIIIAAAggggEBIBAgAhCSjOUwEEEAAAQQQQAABBBBAAIFwCxAACHf+c/QIIIAAAggggAACCCCA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wACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgMrFm1IAABF5SURBVAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBAwAi5ZbAgQIECBAgAABAgQIECAQFTAARIsTmwABAgQIECBAgAABAgQILAIGgEXLLQECBAgQIECAAAECBAgQiAoYAKLFiU2AAAECBAgQIECAAAECBBYBA8Ci5ZYAAQIECBAgQIAAAQIECEQFDADR4sQmQIAAAQIECBAgQIAAAQKLgAFg0XJLgAABAgQIECBAgAABAgSiAgaAaHFiEyBAgAABAgQIECBAgACBRcAAsGi5JUCAAAECBAgQIECAAAECUQEDQLQ4sQkQIECAAAECBAgQIECAwCJgAFi03BIgQIAAAQIECBAgQIAAgaiAASBanNgECBAgQIAAAQIECBAgQGARMAAsWm4JECBAgAABAgQIECBAgEBUwAAQLU5sAgQIECBAgAABAgQIECCwCBgAFi23BAgQIECAAAECBAgQIEAgKmAAiBYnNgECBAgQIECAAAECBAgQWAQMAIuWWwIECBAgQIAAAQIECBAgEBUwAESLE5sAAQIECBAgQIAAAQIECCwCBoBFyy0BAgQIECBAgAABAgQIEIgKGACixYlNgAABAgQIECBAgAABAgQWAQPAouWWAAECBAgQIECAAAECBAhEBQwA0eLEJkCAAAECBAgQIECAAAECi4ABYNFyS4AAAQIECBAgQIAAAQIEogIGgGhxYhMgQIAAAQIECBAgQIAAgUXAALBouSVAgAABAgQIECBAgAABAlEBA0C0OLEJECBAgAABAgQIECBAgMAiYABYtNwSIECAAAECBAgQIECAAIGogAEgWpzYBAgQIECAAAECBAgQIEBgETAALFpuCRAgQIAAAQIECBAgQIBAVMAAEC1ObAIECBAgQIAAAQIECBAgsAgYABYttwQIECBAgAABAgQIECBAICpgAIgWJzYBAgQIECBAgAABAgQIEFgEDACLllsCBAgQIECAAAECBAgQIBAVMABEixObAAECBAgQIECAAAECBAgsAgaARcstAQIECBAgQIAAAQIECBCIChgAosWJTYAAAQIECBAgQIAAAQIEFgEDwKLllgABAgQIECBAgAABAgQIRAUMANHixCZAgAABAgQIECBAgAABAouAAWDRckuAAAECBAgQIECAAAECBKICBoBocWITIECAAAECBAgQIECAAIFFwACwaLklQIAAAQIECBAgQIAAAQJRAQNAtDixCRAgQIAAAQIECBAgQIDAImAAWLTcEiBAgAABAgQIECBAgACBqIABIFqc2AQIECBAgAABAgQIECBAYBEwACxabgkQIECAAAECBAgQIECAQFTAABAtTmwCBAgQIECAAAECBAgQILAIGAAWLbcECBAgQIAAAQIECBAgQCAqYACIFic2AQIECBAgQIAAAQIECBBYBP4BYNIDFE+DzWsAAAAASUVORK5CYII=", "url": "http://www.example.com/", "requestedUrl": "http://www.example.com/", "geometry": [0, 0, 1024, 768], "title": "Example Domain"}',
                    'execDuration' => 0.0832984447479248,
                    'summary' => 'Ran tool splash',
                    'tool' => 'splash',
                    'toolVersion' => '3.5',
                    'outputFormat' => 'json',
                ]]
            ]);
    }

    private function mockGetVulnsScanResultWhenPort80IsClosed()
    {
        ApiUtils::shouldReceive('task_get_scan_public')
            ->once()
            ->with('b9b5e877-bdfe-4b39-8c4b-8316e451730e')
            ->andReturn([
                "hostname" => "www.example.com",
                "ip" => "93.184.215.14",
                "port" => 80,
                "protocol" => "tcp",
                "client" => "",
                "cf_ui_data" => [],
                "tags" => [],
                "tests" => [],
                "scan_type" => "port",
                "first_seen" => null,
                "last_seen" => null,
                "service" => "closed",
                "vendor" => "",
                "product" => null,
                "version" => "",
                "cpe" => null,
                "ssl" => false,
                "current_task" => "alerter",
                "current_task_status" => "DROPPED",
                "current_task_id" => "b9b5e877-bdfe-4b39-8c4b-8316e451730e",
                "current_task_ret" => "",
                "serviceConfidenceScore" => 1,
                "data" => []
            ]);
    }
}
