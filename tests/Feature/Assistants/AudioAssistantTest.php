<?php

namespace Tests\Feature\Assistants;

use App\AgentSquad\Assistants\AudioAssistant;
use App\Enums\LanguageEnum;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDb;

class AudioAssistantTest extends TestCaseWithDb
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
    }

    public function test_audio_assistant_transcribes_audio()
    {
        // Phrase from the hip-hop song “Ice Ice Baby” written by American rapper Vanilla Ice
        $wav_content = base64_decode('SSdtIGtpbGxpbmcgeW91ciBicmFpbiBsaWtlIGEgcG9pc29ub3VzIG11c2hyb29t');
        $audioUrl = 'https://example.com/audio.wav';

        // Fake the audio download
        Http::fake([
            $audioUrl => Http::response($wav_content, 200, ['Content-Type' => 'audio/wav']),
        ]);

        $assistant = AudioAssistant::use()
            ->withLang(LanguageEnum::ENGLISH)
            ->withUrl($audioUrl);

        $transcript = $assistant->text();

        $this->assertIsString($transcript);
        $this->assertStringContainsStringIgnoringCase("I'm killing your brain like a poisonous mushroom", $transcript);
    }
}
