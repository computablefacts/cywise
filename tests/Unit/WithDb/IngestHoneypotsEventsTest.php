<?php

namespace Tests\Unit\WithDb;

use App\Enums\HoneypotCloudProvidersEnum;
use App\Enums\HoneypotCloudSensorsEnum;
use App\Enums\HoneypotStatusesEnum;
use App\Events\IngestHoneypotsEvents;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Models\Attacker;
use App\Models\Honeypot;
use App\Models\HoneypotEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Tests\TestCaseWithDbAndSeeders;

class IngestHoneypotsEventsTest extends TestCaseWithDbAndSeeders
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Disable tests with DB.');
    }
    protected function tearDown(): void
    {
        // $this->markTestSkipped('Disable tests with DB.');
    }

    public function testItIngestHoneypotsEvents()
    {
        ApiUtils::shouldReceive('ip_whois_public')
            ->once()
            ->with('1.1.1.1')
            ->andReturn([
                'data' => [
                    'asn_description' => 'Some Description 1',
                    'asn_registry' => 'Some Registry 1',
                    'asn' => '12345',
                    'asn_cidr' => '1.1.1.0/24',
                    'asn_country_code' => 'US',
                    'asn_date' => '2022-01-01',
                ],
            ]);
        ApiUtils::shouldReceive('ip_whois_public')
            ->once()
            ->with('2.2.2.2')
            ->andReturn([
                'data' => [
                    'asn_description' => 'Some Description 1',
                    'asn_registry' => 'Some Registry 1',
                    'asn' => '12345',
                    'asn_cidr' => '2.2.2.0/24',
                    'asn_country_code' => 'US',
                    'asn_date' => '2022-01-01',
                ],
            ]);

        Auth::login($this->user); // Ensure the honeypot's owner is properly set

        $honeypot1 = Honeypot::create([
            'dns' => 'honeypot1.example.com',
            'status' => HoneypotStatusesEnum::SETUP_COMPLETE,
            'cloud_provider' => HoneypotCloudProvidersEnum::AWS,
            'cloud_sensor' => HoneypotCloudSensorsEnum::HTTPS,
        ]);
        $honeypot2 = Honeypot::create([
            'dns' => 'honeypot2.example.com',
            'status' => HoneypotStatusesEnum::SETUP_COMPLETE,
            'cloud_provider' => HoneypotCloudProvidersEnum::AWS,
            'cloud_sensor' => HoneypotCloudSensorsEnum::HTTPS,
        ]);

        IngestHoneypotsEvents::dispatch(Carbon::now(), 'honeypot1.example.com', $this->firstHoneypotEvents());
        IngestHoneypotsEvents::dispatch(Carbon::now(), 'honeypot2.example.com', $this->secondHoneypotEvents());

        $this->assertCount(2, Honeypot::all());
        $this->assertCount(3, HoneypotEvent::all());
        $this->assertCount(1, Attacker::all());

        $attacker = Attacker::whereNotNull('name')->firstOrFail();

        $this->assertEquals(Carbon::createFromFormat('d/M/Y:H:i:s', '01/Jan/2021:00:00:00')->setTimezone('UTC'), $attacker->first_contact);
        $this->assertEquals(Carbon::createFromFormat('d/M/Y:H:i:s', '01/Jan/2023:00:00:00')->setTimezone('UTC'), $attacker->last_contact);

        $event1 = HoneypotEvent::where('event', 'Event1')->firstOrFail();
        $event2 = HoneypotEvent::where('event', 'Event2')->firstOrFail();
        $event3 = HoneypotEvent::where('event', 'Event3')->firstOrFail();

        $this->assertEquals($honeypot1->id, $event1->honeypot_id);
        $this->assertEquals($attacker->id, $event1->attacker_id);
        $this->assertTrue($event1->human);
        $this->assertFalse($event1->targeted);

        $this->assertEquals($honeypot1->id, $event2->honeypot_id);
        $this->assertEquals($attacker->id, $event2->attacker_id);
        $this->assertFalse($event2->human);
        $this->assertTrue($event2->targeted);

        $this->assertEquals($honeypot2->id, $event3->honeypot_id);
        $this->assertEquals($attacker->id, $event3->attacker_id);
        $this->assertTrue($event3->human);
        $this->assertTrue($event3->targeted);
    }

    private function firstHoneypotEvents(): array
    {
        return [
            [
                'event' => 'Event1',
                'uid' => 'uid1',
                'human' => true,
                'endpoint' => 'endpoint1',
                'timestamp' => '01/Jan/2021:00:00:00 +0000',
                'request_uri' => 'uri1',
                'user_agent' => 'ua1',
                'ip' => '1.1.1.1',
                'details' => 'details1',
                'targeted' => false,
            ],
            [
                'event' => 'Event2',
                'uid' => 'uid1',
                'human' => false,
                'endpoint' => 'endpoint2',
                'timestamp' => '01/Jan/2022:00:00:00 +0000',
                'request_uri' => 'uri2',
                'user_agent' => 'ua2',
                'ip' => '1.1.1.1',
                'details' => 'details2',
                'targeted' => true,
            ],
        ];
    }

    private function secondHoneypotEvents(): array
    {
        return [
            [
                'event' => 'Event3',
                'uid' => 'uid1',
                'human' => true,
                'endpoint' => 'endpoint1',
                'timestamp' => '01/Jan/2023:00:00:00 +0000',
                'request_uri' => 'uri1',
                'user_agent' => 'ua1',
                'ip' => '2.2.2.2',
                'details' => 'details1',
                'targeted' => true,
            ],
        ];
    }
}
