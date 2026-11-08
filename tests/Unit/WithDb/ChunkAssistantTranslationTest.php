<?php

namespace Tests\Unit\WithDb;

use App\AgentSquad\Assistants\ChunkAssistant;
use App\AgentSquad\Assistants\TextAssistant;
use App\Enums\LanguageEnum;
use App\Models\Translation;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDb;
use Mockery;

class ChunkAssistantTranslationTest extends TestCaseWithDb
{

    public function test_it_persists_translation_in_database()
    {
        $sourceText = 'Hello world';
        $translatedText = 'Bonjour le monde';
        $targetLang = LanguageEnum::FRENCH;

        // Mock Http to avoid real API calls for TextAssistant
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => $translatedText,
                    ],
                ]],
            ], 200),
        ]);

        $assistant = ChunkAssistant::use()
            ->withChunk($sourceText)
            ->withLang(LanguageEnum::ENGLISH);

        // First call should trigger the mock and save to DB
        $result1 = $assistant->translate($targetLang);

        $this->assertEquals($translatedText, $result1);
        $this->assertDatabaseHas('translations', [
            'from_lang' => LanguageEnum::ENGLISH->value,
            'to_lang' => $targetLang->value,
            'source_text' => $sourceText,
            'translated_text' => $translatedText,
        ]);

        // Second call should return from DB (mock should not be called again due to 'once()')
        $result2 = $assistant->translate($targetLang);
        $this->assertEquals($translatedText, $result2);
    }
}
