<?php

namespace Tests\Unit\WithDb;

use App\Hashing\TwHasher;
use App\Models\TimelineItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCaseWithDbAndSeeders;

class ItemStoreTest extends TestCaseWithDbAndSeeders
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Disable tests with DB.');
    }
    protected function tearDown(): void
    {
        // $this->markTestSkipped('Disable tests with DB.');
    }

    public function testCreateItem()
    {
        $item = TimelineItem::createItem($this->user->id, 'webpage', Carbon::now(), 0, [
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ]);

        $this->assertEquals(4, $item->facts()->count());
        $this->assertEquals([
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ], $item->attributes());
    }

    public function testHideItem()
    {
        $item = TimelineItem::createItem($this->user->id, 'hide_item', Carbon::now());
        $item->hideItem();
        $item->save();

        $this->assertTrue($item->isHidden());
        $this->assertFalse($item->isDeleted());

        $items = TimelineItem::fetchItems($this->user->id, 'hide_item');

        $this->assertEquals(1, $items->count());

        $items = TimelineItem::fetchItems($this->user->id, 'hide_item', null, null, TimelineItem::FLAG_DELETED);

        $this->assertEquals(0, $items->count());

        $items = TimelineItem::fetchItems($this->user->id, 'hide_item', null, null, TimelineItem::FLAG_HIDDEN);

        $this->assertEquals(1, $items->count());

        $items = TimelineItem::fetchItems($this->user->id, 'hide_item', null, null, 0);

        $this->assertEquals(0, $items->count());
    }

    public function testShowItem()
    {
        $item = TimelineItem::createItem($this->user->id, 'hide_item', Carbon::now(), TimelineItem::FLAG_HIDDEN);

        $this->assertTrue($item->isHidden());
        $this->assertFalse($item->isDeleted());

        $item->showItem();
        $item->save();

        $this->assertFalse($item->isHidden());
        $this->assertFalse($item->isDeleted());
    }

    public function testDeleteItem()
    {
        $item = TimelineItem::createItem($this->user->id, 'delete_item', Carbon::now());
        $item->deleteItem();
        $item->save();

        $this->assertFalse($item->isHidden());
        $this->assertTrue($item->isDeleted());

        $items = TimelineItem::fetchItems($this->user->id, 'delete_item');

        $this->assertEquals(1, $items->count());

        $items = TimelineItem::fetchItems($this->user->id, 'delete_item', null, null, TimelineItem::FLAG_HIDDEN);

        $this->assertEquals(0, $items->count());

        $items = TimelineItem::fetchItems($this->user->id, 'delete_item', null, null, TimelineItem::FLAG_DELETED);

        $this->assertEquals(1, $items->count());

        $items = TimelineItem::fetchItems($this->user->id, 'delete_item', null, null, 0);

        $this->assertEquals(0, $items->count());
    }

    public function testRestoreItem()
    {
        $item = TimelineItem::createItem($this->user->id, 'hide_item', Carbon::now(), TimelineItem::FLAG_DELETED);

        $this->assertFalse($item->isHidden());
        $this->assertTrue($item->isDeleted());

        $item->restoreItem();
        $item->save();

        $this->assertFalse($item->isHidden());
        $this->assertFalse($item->isDeleted());
    }

    public function testAddAttribute()
    {
        $item = TimelineItem::createItem($this->user->id, 'add_attribute', Carbon::now());

        $this->assertEquals(0, $item->facts()->count());
        $this->assertEquals([], $item->attributes());

        $item->addAttribute('pi', 3.14);

        $this->assertEquals(1, $item->facts()->count());
        $this->assertEquals(['pi' => 3.14], $item->attributes());
    }

    public function testUpdateAttribute()
    {
        $item = TimelineItem::createItem($this->user->id, 'update_attribute', Carbon::now(), 0, ['pi' => 3.14]);

        $this->assertEquals(1, $item->facts()->count());
        $this->assertEquals(['pi' => 3.14], $item->attributes());

        $item->updateAttribute('pi', 3.14159265359);

        $this->assertEquals(1, $item->facts()->count());
        $this->assertEquals(['pi' => 3.14159265359], $item->attributes());
    }

    public function testRemoveAttribute()
    {
        $item = TimelineItem::createItem($this->user->id, 'remove_attribute', Carbon::now(), 0, ['pi' => 3.14]);

        $this->assertEquals(1, $item->facts()->count());
        $this->assertEquals(['pi' => 3.14], $item->attributes());

        $item->removeAttribute('pi');

        $this->assertEquals(0, $item->facts()->count());
        $this->assertEquals([], $item->attributes());
    }

    public function testFetchItemsWithTypeFilter()
    {
        $item = TimelineItem::createItem($this->user->id, 'webpage_1', Carbon::now(), 0, [
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ]);

        $items = TimelineItem::fetchItems($this->user->id, 'webpage_1');

        $this->assertEquals(1, $items->count());
    }

    public function testFetchItemsWithTimestampFilters()
    {
        $yesterday = Carbon::yesterday();
        $tomorrow = Carbon::tomorrow();
        $item = TimelineItem::createItem($this->user->id, 'webpage_2', Carbon::now(), 0, [
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ]);

        // after date
        $items = TimelineItem::fetchItems($this->user->id, 'webpage_2', $yesterday);

        $this->assertEquals(1, $items->count());

        // before date
        $items = TimelineItem::fetchItems($this->user->id, 'webpage_2', null, $tomorrow);

        $this->assertEquals(1, $items->count());

        // after date1 AND before date2
        $items = TimelineItem::fetchItems($this->user->id, 'webpage_2', $yesterday, $tomorrow);

        $this->assertEquals(1, $items->count());
    }

    public function testFetchItemsWithAttributeFilters()
    {
        $item = TimelineItem::createItem($this->user->id, 'webpage_3', Carbon::now(), 0, [
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ]);

        // number
        $items = TimelineItem::fetchItems($this->user->id, 'webpage_3', null, null, null, [
            [['id', '=', 1234567890]]
        ]);

        $this->assertEquals(1, $items->count());

        // boolean
        $items = TimelineItem::fetchItems($this->user->id, 'webpage_3', null, null, null, [
            [['crawled', '=', true]]
        ]);

        $this->assertEquals(1, $items->count());

        // string (equal)
        $items = TimelineItem::fetchItems($this->user->id, 'webpage_3', null, null, null, [
            [['title', '=', 'Google']]
        ]);

        $this->assertEquals(1, $items->count());

        // string (like)
        $items = TimelineItem::fetchItems($this->user->id, 'webpage_3', null, null, null, [
            [['url', 'like', '%www.google.com']]
        ]);

        $this->assertEquals(1, $items->count());

        // OR
        $items = TimelineItem::fetchItems($this->user->id, 'webpage_3', null, null, null, [
            [
                ['id', '=', 1234567890],
                ['title', '=', 'Google'],
                ['url', 'like', '%www.google.com'],
                ['crawled', '=', true],
            ]
        ]);

        $this->assertEquals(1, $items->count());

        // AND
        $items = TimelineItem::fetchItems($this->user->id, 'webpage_3', null, null, null, [
            [['id', '=', 1234567890]],
            [['title', '=', 'Google']],
            [['url', 'like', '%www.google.com']],
            [['crawled', '=', true]],
        ]);

        $this->assertEquals(1, $items->count());
    }

    public function testSnooze()
    {
        $timestamp = Carbon::now();
        $item = TimelineItem::createItem($this->user->id, 'snoozed_1', $timestamp, 0, [
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ]);

        $this->assertEquals(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp), $item->timestamp);
        $this->assertFalse($item->isSnoozed());

        // Snooze the event
        $newTimestamp = $timestamp->copy()->addDays(3);
        $newItem = $item->snooze($newTimestamp);

        $this->assertEquals(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp), $item->timestamp);
        $this->assertTrue($item->isSnoozed());

        $this->assertEquals(Carbon::createFromTimestampUTC($newTimestamp->utc()->timestamp), $newItem->timestamp);
        $this->assertFalse($newItem->isSnoozed());

        // Ensure the snoozed event is properly set
        $items = TimelineItem::fetchItems($this->user->id, 'snoozed_1', $newTimestamp);

        $this->assertEquals(1, $items->count());
        $this->assertEquals([
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ], $items->first()->attributes());

        // Ensure deleting the root event does not delete the snoozed one
        $item->deleteItem();
        $item->save();

        $items = TimelineItem::fetchItems($this->user->id, 'snoozed_1', null, $timestamp, 0);

        $this->assertEquals(0, $items->count());

        $items = TimelineItem::fetchItems($this->user->id, 'snoozed_1', $newTimestamp, null, 0);

        $this->assertEquals(1, $items->count());
        $this->assertEquals([
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ], $items->first()->attributes());
    }

    public function testReschedule()
    {
        $timestamp = Carbon::now();
        $item = TimelineItem::createItem($this->user->id, 'rescheduled_1', $timestamp, 0, [
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ]);

        $this->assertEquals(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp), $item->timestamp);
        $this->assertFalse($item->isRescheduled());

        // Reschedule the event
        $newTimestamp = $timestamp->copy()->addDays(3);
        $newItem = $item->reschedule($newTimestamp);

        $this->assertEquals(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp), $item->timestamp);
        $this->assertTrue($item->isRescheduled());

        $this->assertEquals(Carbon::createFromTimestampUTC($newTimestamp->utc()->timestamp), $newItem->timestamp);
        $this->assertFalse($newItem->isRescheduled());

        // Ensure the rescheduled event is properly set
        $items = TimelineItem::fetchItems($this->user->id, 'rescheduled_1', $newTimestamp);

        $this->assertEquals(1, $items->count());
        $this->assertEquals([
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ], $items->first()->attributes());

        // Ensure deleting the root event does not delete the rescheduled one
        $item->deleteItem();
        $item->save();

        $items = TimelineItem::fetchItems($this->user->id, 'rescheduled_1', null, $timestamp, 0);

        $this->assertEquals(0, $items->count());

        $items = TimelineItem::fetchItems($this->user->id, 'rescheduled_1', $newTimestamp, null, 0);

        $this->assertEquals(1, $items->count());
        $this->assertEquals([
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ], $items->first()->attributes());
    }

    public function testShare()
    {
        $timestamp = Carbon::now();
        $item = TimelineItem::createItem($this->user->id, 'shared_1', $timestamp, 0, [
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ]);

        $this->assertEquals(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp), $item->timestamp);
        $this->assertFalse($item->isShared());

        // Share the event
        $user = User::updateOrCreate([
            'email' => 'j.doe@example.com',
        ], [
            'name' => 'J. Doe',
            'email' => 'j.doe@example.com',
            'password' => TwHasher::hash(Str::random()),
        ]);
        $newItem = $item->share($user->id);

        $this->assertEquals(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp), $item->timestamp);
        $this->assertTrue($item->isShared());

        $this->assertEquals(Carbon::createFromTimestampUTC($timestamp->utc()->timestamp), $newItem->timestamp);
        $this->assertFalse($newItem->isShared());

        // Ensure the shared event is properly set
        $items = TimelineItem::fetchItems($user->id, 'shared_1');

        $this->assertEquals(1, $items->count());
        $this->assertEquals([
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ], $items->first()->attributes());

        // Ensure deleting the root event does not delete the shared one
        $item->deleteItem();
        $item->save();

        $items = TimelineItem::fetchItems($this->user->id, 'shared_1', $timestamp, null, 0);

        $this->assertEquals(0, $items->count());

        $items = TimelineItem::fetchItems($user->id, 'shared_1', $timestamp, null, 0);

        $this->assertEquals(1, $items->count());
        $this->assertEquals([
            'id' => 1234567890,
            'url' => 'https://www.google.com',
            'title' => 'Google',
            'crawled' => true,
        ], $items->first()->attributes());
    }
}
