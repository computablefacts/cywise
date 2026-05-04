<?php

declare(strict_types=1);

namespace Tests\Feature\AgentSquad\Providers;

use App\AgentSquad\Providers\MemosProvider;
use App\Models\Tenant;
use App\Models\TimelineItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Tests\TestCaseWithDb;

final class MemosProviderTest extends TestCaseWithDb
{
    protected User $user1;
    protected User $user2;
    protected Tenant $tenant1;
    protected Tenant $tenant2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant1 = Tenant::create(['name' => 'Tenant #1']);
        $this->user1 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
        ]);

        $this->tenant2 = Tenant::create(['name' => 'Tenant #2']);
        $this->user2 = User::factory()->create([
            'tenant_id' => $this->tenant2->id,
        ]);
    }

    public function test_provide_returns_formatted_memos(): void
    {
        Auth::login($this->user1);

        $item = TimelineItem::createItem($this->user1->id, 'note', Carbon::now(), 0, [
            'subject' => 'Test Subject',
            'body' => 'Test Body Content',
            'scopes' => json_encode(['CyberBuddy']),
        ]);

        $result = MemosProvider::use()
            ->withUser($this->user1)
            ->withScope('CyberBuddy')
            ->provide();

        $this->assertStringContainsString('## Memo', $result);
        $this->assertStringContainsString('### Test Subject', $result);
        $this->assertStringContainsString('Test Body Content', $result);
    }

    public function test_provide_returns_empty_string_on_no_memos(): void
    {
        Auth::login($this->user1);

        $result = MemosProvider::use()
            ->withUser($this->user1)
            ->withScope('NonExistentScope')
            ->provide();

        $this->assertEquals('', $result);
    }

    public function test_provide_respects_scope(): void
    {
        Auth::login($this->user1);

        $item = TimelineItem::createItem($this->user1->id, 'note', Carbon::now(), 0, [
            'subject' => 'Test Subject',
            'body' => 'Test Body Content',
            'scopes' => json_encode(['CyberBuddy']),
        ]);

        $result = MemosProvider::use()
            ->withUser($this->user1)
            ->withScope('Orchestrator')
            ->provide();

        $this->assertEquals('', $result);
    }

    public function test_provide_respects_owner(): void
    {
        Auth::login($this->user1);

        $item = TimelineItem::createItem($this->user1->id, 'note', Carbon::now(), 0, [
            'subject' => 'Test Subject',
            'body' => 'Test Body Content',
            'scopes' => json_encode(['CyberBuddy']),
        ]);

        Auth::login($this->user2);

        $result = MemosProvider::use()
            ->withUser($this->user2)
            ->withScope('CyberBuddy')
            ->provide();

        $this->assertEquals('', $result);
    }
}
