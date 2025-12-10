<?php

use App\Events\SendAuditReport;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('event can be instantiated with user and default isOnboarding value', function () {
    $user = User::factory()->create();
    $event = new SendAuditReport($user);

    expect($event->user->id)->toBe($user->id);
    expect($event->isOnboarding)->toBeFalse();
});

test('event can be instantiated with user and isOnboarding true', function () {
    $user = User::factory()->create();
    $event = new SendAuditReport($user, true);

    expect($event->user->id)->toBe($user->id);
    expect($event->isOnboarding)->toBeTrue();
});

test('event is dispatchable', function () {
    $user = User::factory()->create();
    Event::fake();

    SendAuditReport::dispatch($user, true);

    Event::assertDispatched(SendAuditReport::class);
});

test('event contains correct user data', function () {
    $user = User::factory()->create();
    $event = new SendAuditReport($user, false);

    expect($event->user)->toBeInstanceOf(User::class);
    expect($event->user->email)->toBe($user->email);
});

test('listener handles SendAuditReport event', function () {
    $user = User::factory()->create();
    Event::fake();

    SendAuditReport::dispatch($user, true);

    Event::assertDispatched(SendAuditReport::class, function ($event) use ($user) {
        return $event->user->id === $user->id && $event->isOnboarding === true;
    });
});

test('listener processes audit report for non-onboarding users', function () {
    $user = User::factory()->create();
    Event::fake();

    SendAuditReport::dispatch($user, false);

    Event::assertDispatched(SendAuditReport::class, function ($event) use ($user) {
        return $event->isOnboarding === false;
    });
});

test('listener can handle multiple audit report events', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Event::fake();

    SendAuditReport::dispatch($user1, true);
    SendAuditReport::dispatch($user2, false);

    Event::assertDispatchedTimes(SendAuditReport::class, 2);
});

