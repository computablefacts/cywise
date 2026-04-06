<?php

namespace Tests\Feature\AgentSquad\Actions;

use App\AgentSquad\Actions\QueryKnowledgeBase;
use App\AgentSquad\Assistants\TextAssistant;
use App\Models\Chunk;
use App\Models\File;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCaseWithDb;

class QueryKnowledgeBaseTest extends TestCaseWithDb
{
    protected User $user;
    protected Tenant $tenant;
    protected \App\Models\Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Test Tenant']);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->user->init();

        $this->collection = \App\Models\Collection::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Collection',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    public function test_enhance_with_sources_edge_cases(): void
    {
        $action = new QueryKnowledgeBase();
        $method = new \ReflectionMethod(QueryKnowledgeBase::class, 'enhanceWithSources');
        $method->setAccessible(true);

        // Case 1: No citations
        $answer = "This is a simple answer without citations.";
        $enhanced = $method->invoke($action, $answer);
        $this->assertEquals("This is a simple answer without citations.", $enhanced);

        // Case 2: Citation with missing Chunk
        $answer = "According to [[Note 9999]], something happens.";
        $enhanced = $method->invoke($action, $answer);
        $this->assertEquals("According to , something happens.", $enhanced);

        // Case 3: Citation with existing Chunk and File
        $file = File::create([
            'tenant_id' => $this->tenant->id,
            'collection_id' => $this->collection->id,
            'name' => 'test_doc',
            'name_normalized' => 'test_doc',
            'extension' => 'pdf',
            'path' => 'path/to/test_doc.pdf',
            'size' => 1024,
            'md5' => 'md5',
            'sha1' => 'sha1',
            'mime_type' => 'application/pdf',
            'secret' => 'file_secret',
            'created_by' => $this->user->id,
        ]);

        $chunk = Chunk::create([
            'tenant_id' => $this->tenant->id,
            'collection_id' => $this->collection->id,
            'file_id' => $file->id,
            'page' => 5,
            'text' => 'ESSENTIAL DIRECTIVE: You must do this.',
            'created_by' => $this->user->id,
        ]);

        $answer = "As seen in [[Note {$chunk->id}]].";
        $enhanced = $method->invoke($action, $answer);

        $this->assertStringContainsString("<b style=\"color:#1DD288\">[{$chunk->id}]</b>", $enhanced);
        $this->assertStringContainsString("Sources :", $enhanced);
        $this->assertStringContainsString("test_doc.pdf", $enhanced);
        $this->assertStringContainsString("p. 5", $enhanced);
        $this->assertStringContainsString("ESSENTIAL DIRECTIVE", $enhanced);

        // Case 4: Different directive colors
        $chunkStandard = Chunk::create([
            'tenant_id' => $this->tenant->id,
            'collection_id' => $this->collection->id,
            'file_id' => $file->id,
            'text' => 'STANDARD DIRECTIVE: Should do this.',
            'created_by' => $this->user->id,
        ]);
        $answerStandard = "Standard [[Note {$chunkStandard->id}]]";
        $enhancedStandard = $method->invoke($action, $answerStandard);
        $this->assertStringContainsString("color:#C5C3C3", $enhancedStandard);

        $chunkAdvanced = Chunk::create([
            'tenant_id' => $this->tenant->id,
            'collection_id' => $this->collection->id,
            'file_id' => $file->id,
            'text' => 'ADVANCED DIRECTIVE: Might do this.',
            'created_by' => $this->user->id,
        ]);
        $answerAdvanced = "Advanced [[Note {$chunkAdvanced->id}]]";
        $enhancedAdvanced = $method->invoke($action, $answerAdvanced);
        $this->assertStringContainsString("color:#FDC99D", $enhancedAdvanced);

        $chunkOther = Chunk::create([
            'tenant_id' => $this->tenant->id,
            'collection_id' => $this->collection->id,
            'file_id' => $file->id,
            'text' => 'Normal text.',
            'created_by' => $this->user->id,
        ]);
        $answerOther = "Other [[Note {$chunkOther->id}]]";
        $enhancedOther = $method->invoke($action, $answerOther);
        $this->assertStringContainsString("color:#F8B500", $enhancedOther);

        // Case 5: Remove [[Memo ...]]
        $answerWithMemo = "Check this [[Memo A1]] and [[Note {$chunk->id}]].";
        $enhancedWithMemo = $method->invoke($action, $answerWithMemo);
        $this->assertStringNotContainsString("Memo A1", $enhancedWithMemo);
        $this->assertStringContainsString("[{$chunk->id}]", $enhancedWithMemo);
    }

    public function test_french_questions_trigger_anssi_lookup(): void
    {
        $action = new QueryKnowledgeBase();
        $answer = $action->execute($this->user, 'thread-fr', [], 'Ai-je le droit d\'utiliser une clef USB ?');

        $this->assertNotNull($answer);
        
        $markdown = $answer->markdown();

        $this->assertNotEmpty($markdown);
        $this->assertStringContainsString('USB', $markdown);

        // Semantic check using TextAssistant
        $prompt = "Compare the following two texts and determine if they have mostly the same meaning. 
        Respond with exactly 'YES' if they match, or 'NO' followed by a brief explanation if they don't.
        
        Text 1 (Markdown Answer):
        {$markdown}
        
        Text 2 (Source Reference):
        Une clé USB peut être utilisée, mais avec des précautions : ne pas utiliser de clés inconnues, et limiter l'usage de clés dont l'intégrité n'est pas garantie en les scannant avec un antivirus.
        ";

        $verification = TextAssistant::use()
            ->withRawPrompt($prompt)
            ->text();

        $this->assertStringContainsString('YES', $verification, "Semantic mismatch in French test: {$verification}");
    }

    public function test_english_questions_trigger_rowden_lookup(): void
    {
        $action = new QueryKnowledgeBase();
        $answer = $action->execute($this->user, 'thread-en', [], 'How to minimize risks of removable media for remote workers?');

        $this->assertNotNull($answer);

        $markdown = $answer->markdown();

        $this->assertNotEmpty($markdown);
        $this->assertStringContainsString('removable media', Str::lower($markdown));

        // Semantic check using TextAssistant
        $prompt = "Compare the following two texts and determine if they have mostly the same meaning. 
        Respond with exactly 'YES' if they match, or 'NO' followed by a brief explanation if they don't.
        
        Text 1 (Markdown Answer):
        {$markdown}
        
        Text 2 (Source Reference):
        To minimize risks of removable media, disable them via MDM, use antivirus, only use corporate-supplied media, and encrypt data. Use corporate storage instead of USB drives.
        ";

        $verification = TextAssistant::use()
            ->withRawPrompt($prompt)
            ->text();

        $this->assertStringContainsString('YES', $verification, "Semantic mismatch in English test: {$verification}");
    }
}
