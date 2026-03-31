<?php

namespace Tests\Feature\Assistants;

use App\AgentSquad\Assistants\ChunkAssistant;
use App\Enums\LanguageEnum;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCaseWithDb;

class ChunkAssistantTest extends TestCaseWithDb
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

    public function test_chunk_assistant_embedding_returns_array_of_floats()
    {
        $assistant = ChunkAssistant::use()
            ->withChunk('Ceci est un test pour les embeddings.');

        $embedding = $assistant->embedding();

        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
        $this->assertCount(1024, $embedding);

        foreach ($embedding as $f) {
            $this->assertIsFloat($f);
        }
    }

    public function test_chunk_assistant_translate_returns_translated_text()
    {
        $assistant = ChunkAssistant::use()
            ->withChunk('Hello world')
            ->withLang(LanguageEnum::ENGLISH);

        $translated = $assistant->translate();

        $this->assertIsString($translated);
        $this->assertNotEmpty($translated);
        $this->assertStringContainsStringIgnoringCase('Bonjour monde', $translated);
    }

    public function test_chunk_assistant_hypothetical_questions_returns_array()
    {
        $assistant = ChunkAssistant::use()
            ->withChunk('La capitale de la France est Paris. C\'est une ville magnifique.')
            ->withLang(LanguageEnum::FRENCH);

        $questions = $assistant->hypotheticalQuestions();

        $this->assertIsArray($questions);
        $this->assertNotEmpty($questions);

        foreach ($questions as $q) {

            $this->assertArrayHasKey('question', $q);
            $this->assertArrayHasKey('language', $q);
            $this->assertArrayHasKey('embedding', $q);
            $this->assertCount(1024, $q['embedding']);

            foreach ($q['embedding'] as $f) {
                $this->assertIsFloat($f);
            }
        }
    }
}
