<?php

namespace Tests\Unit;

use App\Listeners\GenerateAiRemediationListener;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Port;
use App\Models\Scan;
use App\Models\YnhOsquery;
use App\Models\YnhServer;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDb;

class GenerateAiRemediationListenerTest extends TestCaseWithDb
{
    public function test_ai_remediation_prompt_includes_asset_operating_system(): void
    {
        $this->actingAs(tenant1User());

        $ip = '10.10.10.42';
        $capturedPrompt = null;

        $server = YnhServer::factory()->create([
            'ip_address' => $ip,
        ]);

        YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'columns' => [
                'arch' => 'x86_64',
                'codename' => 'bullseye',
                'major' => 11,
                'minor' => 0,
                'patch' => 0,
                'platform' => 'debian',
            ],
        ]);

        $asset = Asset::factory()->monitored()->create([
            'asset' => $ip,
        ]);
        $scan = Scan::factory()->for($asset)->create();
        $port = Port::factory()->for($scan)->create([
            'ip' => $ip,
            'service' => 'nginx',
        ]);
        $alert = Alert::factory()->create([
            'port_id' => $port->id,
            'type' => 'generic_alert',
            'title' => 'Test vulnerability',
            'vulnerability' => 'A test vulnerability',
            'remediation' => 'Apply the test fix',
            'cve_id' => null,
        ]);

        Http::fake(function ($request) use (&$capturedPrompt) {
            $capturedPrompt = $request->data()['messages'][0]['content'] ?? null;

            return Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'AI remediation response',
                        ],
                    ],
                ],
            ], 200);
        });

        $listener = new GenerateAiRemediationListener();
        $method = new \ReflectionMethod($listener, 'generateAiRemediation');
        $method->setAccessible(true);

        $result = $method->invoke($listener, $port, $alert, 'explanation');

        $this->assertSame('AI remediation response', $result['content']);
        $this->assertStringContainsString('<operating_system>debian bullseye 11.0.0</operating_system>', $capturedPrompt);
        $this->assertStringContainsString('<title>Test vulnerability</title>', $capturedPrompt);
    }
}
