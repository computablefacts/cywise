<?php

use App\Models\YnhOsquery;
use App\Models\YnhOsqueryRule;
use App\Models\YnhServer;
use Carbon\Carbon;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

describe('events@list', function () {

    test('excludes dismissed events by default', function () {
        asTenant1User();
        $server = YnhServer::factory()->create(['created_by' => tenant1User()->id]);
        $rule = YnhOsqueryRule::factory()->create(['enabled' => true, 'score' => 50]);
        
        // Create two events with different rules
        $rule2 = YnhOsqueryRule::factory()->create(['enabled' => true, 'score' => 50]);

        $event1 = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $rule->id,
            'calendar_time' => Carbon::now(),
            'dismissed' => false,
        ]);
        
        $event2 = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $rule2->id,
            'calendar_time' => Carbon::now(),
            'dismissed' => true,
        ]);

        $result = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('events@list', [
                'min_score' => 0,
                'window' => [
                    Carbon::now()->subDays(1)->toDateTimeString(),
                    Carbon::now()->addDays(1)->toDateTimeString(),
                ]
            ]);
        
        $result->assertJsonCount(1, 'result.events')
            ->assertJsonFragment(['id' => $event1->id])
            ->assertJsonMissing(['id' => $event2->id]);
    });

    test('includes dismissed events when include_dismissed is true', function () {
        asTenant1User();
        $server = YnhServer::factory()->create(['created_by' => tenant1User()->id]);
        $rule = YnhOsqueryRule::factory()->create(['enabled' => true, 'score' => 50]);
        
        $event1 = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $rule->id,
            'calendar_time' => Carbon::now(),
            'dismissed' => false,
        ]);
        
        $event2 = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $rule->id,
            'calendar_time' => Carbon::now(),
            'dismissed' => true,
        ]);

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('events@list', [
                'min_score' => 0,
                'include_dismissed' => true,
                'window' => [
                    Carbon::now()->subDays(1)->toDateTimeString(),
                    Carbon::now()->addDays(1)->toDateTimeString(),
                ]
            ])
            ->assertJsonCount(2, 'result.events')
            ->assertJsonFragment(['id' => $event1->id])
            ->assertJsonFragment(['id' => $event2->id]);
    });

});

describe('events@undismiss', function () {

    test('marks an event as not dismissed', function () {
        asTenant1User();
        $server = YnhServer::factory()->create(['created_by' => tenant1User()->id]);
        $rule = YnhOsqueryRule::factory()->create(['enabled' => true, 'score' => 50]);
        
        $event = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $rule->id,
            'calendar_time' => Carbon::now(),
            'dismissed' => true,
        ]);

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('events@undismiss', [
                'event_id' => $event->id,
            ])
            ->assertJsonFragment(['msg' => 'The event has been undismissed!']);

        expect($event->refresh()->dismissed)->toBeFalse();
    });

});

describe('events@dismiss', function () {

    test('marks an event as dismissed', function () {
        asTenant1User();
        $server = YnhServer::factory()->create(['created_by' => tenant1User()->id]);
        $rule = YnhOsqueryRule::factory()->create(['enabled' => true, 'score' => 50]);
        
        $event = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $rule->id,
            'calendar_time' => Carbon::now(),
            'dismissed' => false,
        ]);

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('events@dismiss', [
                'event_id' => $event->id,
            ])
            ->assertJsonFragment(['msg' => 'The event has been dismissed!']);

        expect($event->refresh()->dismissed)->toBeTrue();
    });

});
