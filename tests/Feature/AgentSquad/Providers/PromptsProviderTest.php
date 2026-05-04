<?php

declare(strict_types=1);

namespace Tests\Feature\AgentSquad\Providers;

use App\AgentSquad\Providers\PromptsProvider;
use App\Models\Prompt;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCaseWithDb;

final class PromptsProviderTest extends TestCaseWithDb
{
    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Test Tenant']);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Auth::login($this->user);
    }

    public function test_provide_replaces_variables_in_prompt(): void
    {
        Prompt::create([
            'name' => 'test_prompt',
            'template' => 'Hello {name}, welcome to {place}!',
            'created_by' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $result = PromptsProvider::use()
            ->withName('test_prompt')
            ->withVariables([
                'name' => 'Alice',
                'place' => 'Wonderland'
            ])
            ->provide();

        $this->assertEquals('Hello Alice, welcome to Wonderland!', $result);
    }

    public function test_provide_returns_empty_string_if_prompt_not_found(): void
    {
        $result = PromptsProvider::use()
            ->withName('non_existent_prompt')
            ->provide();

        $this->assertEquals('', $result);
    }
}
