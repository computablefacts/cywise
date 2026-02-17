<?php

namespace Tests\Feature;

use App\Models\TimelineItem;
use App\Models\User;
use Illuminate\Support\Carbon;

it('creates an item', function () {
    asTenant1User();
    $item = TimelineItem::createItem(tenant1User()->id, 'webpage', now(), 0, [
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);

    expect($item->facts()->count())->toBe(4);
    expect($item->attributes())->toEqual([
        'id' => '1234567890',
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);
});

it('hides an item', function () {
    asTenant1User();
    $item = TimelineItem::createItem(tenant1User()->id, 'hide_item', now());
    $item->hideItem();
    $item->save();

    expect($item->isHidden())->toBeTrue();
    expect($item->isDeleted())->toBeFalse();

    expect(TimelineItem::fetchItems(tenant1User()->id, 'hide_item'))->toHaveCount(1);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'hide_item', null, null, TimelineItem::FLAG_DELETED))->toHaveCount(0);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'hide_item', null, null, TimelineItem::FLAG_HIDDEN))->toHaveCount(1);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'hide_item', null, null, 0))->toHaveCount(0);
});

it('shows an item', function () {
    asTenant1User();
    $item = TimelineItem::createItem(tenant1User()->id, 'hide_item', now(), TimelineItem::FLAG_HIDDEN);

    expect($item->isHidden())->toBeTrue();
    expect($item->isDeleted())->toBeFalse();

    $item->showItem();
    $item->save();

    expect($item->isHidden())->toBeFalse();
    expect($item->isDeleted())->toBeFalse();
});

it('deletes an item', function () {
    asTenant1User();
    $item = TimelineItem::createItem(tenant1User()->id, 'delete_item', now());
    $item->deleteItem();
    $item->save();

    expect($item->isHidden())->toBeFalse();
    expect($item->isDeleted())->toBeTrue();

    expect(TimelineItem::fetchItems(tenant1User()->id, 'delete_item'))->toHaveCount(1);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'delete_item', null, null, TimelineItem::FLAG_HIDDEN))->toHaveCount(0);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'delete_item', null, null, TimelineItem::FLAG_DELETED))->toHaveCount(1);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'delete_item', null, null, 0))->toHaveCount(0);
});

it('restores an item', function () {
    asTenant1User();
    $item = TimelineItem::createItem(tenant1User()->id, 'hide_item', now(), TimelineItem::FLAG_DELETED);

    expect($item->isHidden())->toBeFalse();
    expect($item->isDeleted())->toBeTrue();

    $item->restoreItem();
    $item->save();

    expect($item->isHidden())->toBeFalse();
    expect($item->isDeleted())->toBeFalse();
});

it('adds an attribute', function () {
    asTenant1User();
    $item = TimelineItem::createItem(tenant1User()->id, 'add_attribute', now());

    expect($item->facts()->count())->toBe(0);
    expect($item->attributes())->toBeEmpty();

    $item->addAttribute('pi', 3.14);

    expect($item->facts()->count())->toBe(1);
    expect($item->attributes())->toEqual(['pi' => 3.14]);
});

it('updates an attribute', function () {
    asTenant1User();
    $item = TimelineItem::createItem(tenant1User()->id, 'update_attribute', now(), 0, ['pi' => 3.14]);

    expect($item->facts()->count())->toBe(1);
    expect($item->attributes())->toEqual(['pi' => 3.14]);

    $item->updateAttribute('pi', 3.14159265359);

    expect($item->facts()->count())->toBe(1);
    expect($item->attributes())->toEqual(['pi' => 3.14159265359]);
});

it('removes an attribute', function () {
    asTenant1User();
    $item = TimelineItem::createItem(tenant1User()->id, 'remove_attribute', now(), 0, ['pi' => 3.14]);

    expect($item->facts()->count())->toBe(1);
    expect($item->attributes())->toEqual(['pi' => 3.14]);

    $item->removeAttribute('pi');

    expect($item->facts()->count())->toBe(0);
    expect($item->attributes())->toBeEmpty();
});

it('fetch items with type filter', function () {
    asTenant1User();
    TimelineItem::createItem(tenant1User()->id, 'webpage_1', now(), 0, [
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);

    expect(TimelineItem::fetchItems(tenant1User()->id, 'webpage_1'))->toHaveCount(1);
});

it('fetch items with timestamp filters', function () {
    $yesterday = Carbon::yesterday();
    $tomorrow = Carbon::tomorrow();

    asTenant1User();
    TimelineItem::createItem(tenant1User()->id, 'webpage_2', now(), 0, [
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);

    expect(TimelineItem::fetchItems(tenant1User()->id, 'webpage_2', $yesterday))->toHaveCount(1);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'webpage_2', null, $tomorrow))->toHaveCount(1);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'webpage_2', $yesterday, $tomorrow))->toHaveCount(1);
});

it('fetch items with attribute filters', function () {
    asTenant1User();
    TimelineItem::createItem(tenant1User()->id, 'webpage_3', now(), 0, [
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);

    // number
    expect(TimelineItem::fetchItems(tenant1User()->id, 'webpage_3', null, null, null, [
        [['id', '=', 1234567890]]
    ]))->toHaveCount(1);

    // boolean
    expect(TimelineItem::fetchItems(tenant1User()->id, 'webpage_3', null, null, null, [
        [['crawled', '=', true]]
    ]))->toHaveCount(1);

    // string (equal)
    expect(TimelineItem::fetchItems(tenant1User()->id, 'webpage_3', null, null, null, [
        [['title', '=', 'Google']]
    ]))->toHaveCount(1);

    // string (like)
    expect(TimelineItem::fetchItems(tenant1User()->id, 'webpage_3', null, null, null, [
        [['url', 'like', '%www.google.com']]
    ]))->toHaveCount(1);

    // OR
    expect(TimelineItem::fetchItems(tenant1User()->id, 'webpage_3', null, null, null, [
        [
            ['id', '=', 1234567890],
            ['title', '=', 'Google'],
            ['url', 'like', '%www.google.com'],
            ['crawled', '=', true],
        ]
    ]))->toHaveCount(1);

    // AND
    expect(TimelineItem::fetchItems(tenant1User()->id, 'webpage_3', null, null, null, [
        [['id', '=', 1234567890]],
        [['title', '=', 'Google']],
        [['url', 'like', '%www.google.com']],
        [['crawled', '=', true]],
    ]))->toHaveCount(1);
});

it('snoozes an item', function () {
    asTenant1User();
    $timestamp = now();
    $item = TimelineItem::createItem(tenant1User()->id, 'snoozed_1', $timestamp, 0, [
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);

    expect($item->timestamp)->toEqual(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp));
    expect($item->isSnoozed())->toBeFalse();

    // Snooze the event
    $newTimestamp = $timestamp->copy()->addDays(3);
    $newItem = $item->snooze($newTimestamp);

    expect($item->timestamp)->toEqual(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp));
    expect($item->isSnoozed())->toBeTrue();
    expect($newItem->timestamp)->toEqual(Carbon::createFromTimestampUTC($newTimestamp->utc()->timestamp));
    expect($newItem->isSnoozed())->toBeFalse();

    // Check snoozed event
    $items = TimelineItem::fetchItems(tenant1User()->id, 'snoozed_1', $newTimestamp);
    expect($items)->toHaveCount(1);
    expect($items->first()->attributes())->toEqual([
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);

    // Check deletion
    $item->deleteItem();
    $item->save();
    expect(TimelineItem::fetchItems(tenant1User()->id, 'snoozed_1', null, $timestamp, 0))->toHaveCount(0);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'snoozed_1', $newTimestamp, null, 0))->toHaveCount(1);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'snoozed_1', $newTimestamp)->first()->attributes())->toEqual([
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);
});

it('reschedules an item', function () {
    asTenant1User();
    $timestamp = now();
    $item = TimelineItem::createItem(tenant1User()->id, 'rescheduled_1', $timestamp, 0, [
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);

    expect($item->timestamp)->toEqual(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp));
    expect($item->isRescheduled())->toBeFalse();

    // Reschedule the event
    $newTimestamp = $timestamp->copy()->addDays(3);
    $newItem = $item->reschedule($newTimestamp);

    expect($item->timestamp)->toEqual(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp));
    expect($item->isRescheduled())->toBeTrue();
    expect($newItem->timestamp)->toEqual(Carbon::createFromTimestampUTC($newTimestamp->utc()->timestamp));
    expect($newItem->isRescheduled())->toBeFalse();

    // Check rescheduled event
    $items = TimelineItem::fetchItems(tenant1User()->id, 'rescheduled_1', $newTimestamp);
    expect($items)->toHaveCount(1);
    expect($items->first()->attributes())->toEqual([
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);

    // Check deletion
    $item->deleteItem();
    $item->save();
    expect(TimelineItem::fetchItems(tenant1User()->id, 'rescheduled_1', null, $timestamp, 0))->toHaveCount(0);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'rescheduled_1', $newTimestamp, null, 0))->toHaveCount(1);
    expect(TimelineItem::fetchItems(tenant1User()->id, 'rescheduled_1', $newTimestamp)->first()->attributes())->toEqual([
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);
});

it('shares an item', function () {
    asTenant1User();
    $timestamp = now();
    $item = TimelineItem::createItem(tenant1User()->id, 'shared_1', $timestamp, 0, [
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);

    expect($item->timestamp)->toEqual(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp));
    expect($item->isShared())->toBeFalse();

    // Share the event
    asTenant2User();
    // $user = User::updateOrCreate([
    //     'email' => 'j.doe@example.com',
    // ], [
    //     'name' => 'J. Doe',
    //     'email' => 'j.doe@example.com',
    //     'password' => TwHasher::hash(Str::random()),
    // ]);
    $newItem = $item->share(tenant2User()->id);

    expect($item->timestamp)->toEqual(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp));
    expect($item->isShared())->toBeTrue();
    expect($newItem->timestamp)->toEqual(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp));
    expect($newItem->isShared())->toBeFalse();

    // Check shared event
    $items = TimelineItem::fetchItems(tenant2User()->id, 'shared_1');
    expect($items)->toHaveCount(1);
    expect($items->first()->attributes())->toEqual([
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);

    // Check deletion
    $item->deleteItem();
    $item->save();
    expect(TimelineItem::fetchItems(tenant1User()->id, 'shared_1', $timestamp, null, 0))->toHaveCount(0);
    expect(TimelineItem::fetchItems(tenant2User()->id, 'shared_1', $timestamp, null, 0))->toHaveCount(1);
    expect(TimelineItem::fetchItems(tenant2User()->id, 'shared_1', $timestamp)->first()->attributes())->toEqual([
        'id' => 1234567890,
        'url' => 'https://www.google.com',
        'title' => 'Google',
        'crawled' => true,
    ]);
});
