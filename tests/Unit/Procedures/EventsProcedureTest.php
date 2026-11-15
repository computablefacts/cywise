<?php

use App\Models\YnhServer;
use App\Models\YnhOsquery;
use App\Models\YnhOsqueryRule;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

describe('events@list', function () {

    test('list can show dismissed events', function () {
        asTenant1User();
        $server = YnhServer::factory()->create(['created_by' => tenant1User()->id]);
        $rule = YnhOsqueryRule::factory()->create([
            'created_by' => tenant1User()->id, 
            'enabled' => true, 
            'score' => 50,
            'name' => 'test_rule'
        ]);
        
        $calendarTime = now()->subHour()->startOfHour();

        $eventDismissed = new YnhOsquery();
        $eventDismissed->ynh_server_id = $server->id;
        $eventDismissed->ynh_osquery_rule_id = $rule->id;
        $eventDismissed->dismissed = true;
        $eventDismissed->calendar_time = $calendarTime;
        $eventDismissed->unix_time = $calendarTime->timestamp;
        $eventDismissed->action = 'added';
        $eventDismissed->name = 'test_event';
        $eventDismissed->columns = ['foo' => 'bar'];
        $eventDismissed->row = 0;
        $eventDismissed->epoch = 0;
        $eventDismissed->counter = 0;
        $eventDismissed->numerics = false;
        $eventDismissed->packed = true;
        $eventDismissed->host_identifier = 'test';
        $eventDismissed->save();

        $eventNotDismissed = new YnhOsquery();
        $eventNotDismissed->ynh_server_id = $server->id;
        $eventNotDismissed->ynh_osquery_rule_id = $rule->id;
        $eventNotDismissed->dismissed = false;
        $eventNotDismissed->calendar_time = $calendarTime;
        $eventNotDismissed->unix_time = $calendarTime->timestamp;
        $eventNotDismissed->action = 'added';
        $eventNotDismissed->name = 'test_event';
        $eventNotDismissed->columns = ['foo' => 'bar'];
        $eventNotDismissed->row = 0;
        $eventNotDismissed->epoch = 0;
        $eventNotDismissed->counter = 0;
        $eventNotDismissed->numerics = false;
        $eventNotDismissed->packed = true;
        $eventNotDismissed->host_identifier = 'test';
        $eventNotDismissed->save();

        // By default, dismissed events are NOT shown
        $this->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('events@list', ['min_score' => 0, 'server_id' => $server->id])
            ->assertJsonCount(1, 'result.events')
            ->assertJsonFragment(['id' => $eventNotDismissed->id])
            ->assertJsonMissing(['id' => $eventDismissed->id]);

        // With show_dismissed => true, dismissed events SHOULD be shown
        $this->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('events@list', [
                'min_score' => 0, 
                'server_id' => $server->id,
                'show_dismissed' => true
            ])
            ->assertJsonCount(2, 'result.events')
            ->assertJsonFragment(['id' => $eventNotDismissed->id])
            ->assertJsonFragment(['id' => $eventDismissed->id]);
    });
});

describe('events@undismiss', function () {

    test('undismiss removes dismissed entry', function () {
        asTenant1User();
        $server = YnhServer::factory()->create(['created_by' => tenant1User()->id]);
        $rule = YnhOsqueryRule::factory()->create(['created_by' => tenant1User()->id, 'enabled' => true, 'score' => 50]);
        
        $event = YnhOsquery::factory()->create([
            'ynh_server_id' => $server->id,
            'ynh_osquery_rule_id' => $rule->id,
            'dismissed' => true,
        ]);

        expect($event->fresh()->dismissed)->toBeTrue();

        $this->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('events@undismiss', [
                'event_id' => $event->id,
            ])
            ->assertJsonFragment(['msg' => 'The event has been unmasked!']);

        expect($event->fresh()->dismissed)->toBeFalse();
    });
});
