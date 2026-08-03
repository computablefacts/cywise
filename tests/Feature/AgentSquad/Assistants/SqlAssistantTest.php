<?php

namespace Tests\Feature\AgentSquad\Assistants;

use App\AgentSquad\Assistants\SqlAssistant;
use App\Models\Prompt;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCaseWithDb;

class SqlAssistantTest extends TestCaseWithDb
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

    public function test_sql_assistant_generates_sql()
    {
        Prompt::create([
            'name' => 'default_clickhouse_query_generation',
            'template' => "Given the schema:\n{SCHEMA}\n\nGenerate a SQL query for: {QUESTION}. Respond only with SQL.",
            'created_by' => $this->user->id,
        ]);

        $table = new Table([
            'name' => 'users',
            'description' => 'A table of users',
            'schema' => [
                ['new_name' => 'ID', 'type' => 'Int64'],
                ['new_name' => 'NAME', 'type' => 'String'],
                ['new_name' => 'EMAIL', 'type' => 'String'],
            ],
            'created_by' => $this->user->id,
        ]);
        // Note: we don't necessarily need to save it to DB since we pass a Collection to SqlAssistant, 
        // but SqlAssistant uses Table model type hint.

        $assistant = SqlAssistant::use()
            ->withTables(collect([$table]))
            ->withAnalyticalQuestion('Combien y a-t-il d\'utilisateurs ?');

        $sql = $assistant->sql();

        $this->assertIsString($sql);
        $this->assertNotEmpty($sql);
        $this->assertStringContainsStringIgnoringCase('SELECT COUNT(*) FROM users', $sql);
    }

    public function test_normalize_table_name()
    {
        $this->assertEquals('my_table', SqlAssistant::normalizeTableName('My Table.csv'));
        $this->assertEquals('data_file', SqlAssistant::normalizeTableName('/path/to/data-file.json'));
    }

    public function test_normalize_column_name()
    {
        $this->assertEquals('USER_NAME', SqlAssistant::normalizeColumnName('User Name'));
        $this->assertEquals('ID', SqlAssistant::normalizeColumnName('id'));
    }
}
