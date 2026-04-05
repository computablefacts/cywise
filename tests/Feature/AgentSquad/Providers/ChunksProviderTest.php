<?php

declare(strict_types=1);

namespace Tests\Feature\AgentSquad\Providers;

use App\AgentSquad\Providers\ChunksProvider;
use App\Enums\LanguageEnum;
use App\Events\IngestFile;
use App\Jobs\EmbedChunk;
use App\Listeners\IngestFileListener;
use App\Models\Chunk;
use App\Models\Collection;
use App\Models\File;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCaseWithDb;

final class ChunksProviderTest extends TestCaseWithDb
{
    protected User $user;
    protected Tenant $tenant;
    protected Collection $collection;
    protected File $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Test Tenant']);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Auth::login($this->user);

        $this->collection = Collection::create([
            'name' => 'testlgfr',
            'priority' => 1,
            'is_deleted' => false,
            'created_by' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Simuler le stockage S3
        Storage::fake('files-s3');
        $filePath = 'test.txt';
        Storage::disk('files-s3')->put("testlgfr/{$filePath}", "Le ciel est bleu aujourd'hui. L'herbe est verte en été.");

        $this->file = File::create([
            'collection_id' => $this->collection->id,
            'name' => 'test.txt',
            'name_normalized' => 'test.txt',
            'extension' => 'txt',
            'path' => $filePath,
            'size' => 10,
            'md5' => md5("Le ciel est bleu aujourd'hui. L'herbe est verte en été."),
            'sha1' => sha1("Le ciel est bleu aujourd'hui. L'herbe est verte en été."),
            'mime_type' => 'text/plain',
            'secret' => 'some-secret',
            'is_deleted' => false,
            'is_embedded' => false,
            'created_by' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        Http::fake([
            '*/api/file-input' => Http::response([
                'error' => false,
                'response' => [
                    [
                        'text' => "Le ciel est bleu aujourd'hui.",
                        'metadata' => [
                            'title' => 'Titre > Sous-titre',
                            'page_idx' => 0,
                            'tag' => 'para'
                        ]
                    ],
                    [
                        'text' => "L'herbe est verte en été.",
                        'metadata' => [
                            'title' => 'Titre > Autre Sous-titre',
                            'page_idx' => 1,
                            'tag' => 'para'
                        ]
                    ]
                ]
            ]),
        ]);

        // Extraire les chunks via IngestFileListener
        $event = new IngestFile($this->user, $this->collection->name, $this->file->id);
        (new IngestFileListener())->handle($event);

        $chunks = Chunk::where('file_id', $this->file->id)->get();
        $this->assertCount(2, $chunks);

        // Extraire les embeddings via EmbedChunk
        /** @var Chunk $chunk */
        foreach ($chunks as $chunk) {

            $this->assertFalse($chunk->fresh()->is_embedded);
            $this->assertEquals(0, $chunk->vectors()->count());

            (new EmbedChunk($chunk->id))->handle();

            $this->assertTrue($chunk->fresh()->is_embedded);
            $this->assertEquals(1, $chunk->vectors()->count());

            /** @var Vector $vector */
            foreach ($chunk->vectors as $vector) {
                $this->assertEquals($vector->locale, LanguageEnum::FRENCH->value);
                $this->assertContainsEquals($vector->hypothetical_question, [
                    "Quelle est la couleur du ciel aujourd'hui ?",
                    "Quelle est la couleur de l'herbe en été ?"
                ]);
            }

            // Full-text search
            $chunk->searchableSync();

            $count = DB::table('searchindex')
                ->select()
                ->where('record_id', $chunk->id)
                ->count();

            $this->assertEquals(1, $count);
        }

        $count = DB::table('searchindex')
            ->select()
            ->count();

        $this->assertEquals(2, $count);
    }

    public function test_provide_extracts_chunks_and_embeddings(): void
    {
        \Illuminate\Support\Facades\Cache::clear();

        $results = ChunksProvider::use()
            ->withCollections(collect([$this->collection]))
            ->withLang(LanguageEnum::FRENCH)
            ->withText('ciel bleu') // triggers vector search
            ->withLimit(5)
            ->provide();

        $this->assertEquals(2, $results->count());

        /** @var Chunk $ciel */
        $ciel = $results->first();

        $this->assertEquals("Le ciel est bleu aujourd'hui.", $ciel->text);
        $this->assertEquals(0.7, $ciel->_score);

        /** @var Chunk $herbe */
        $herbe = $results->last();

        $this->assertEquals("L'herbe est verte en été.", $herbe->text);
        $this->assertEquals(0.0, $herbe->_score);
    }

    public function test_provide_extracts_chunks_and_searches(): void
    {
        \Illuminate\Support\Facades\Cache::clear();

        $results = ChunksProvider::use()
            ->withCollections(collect([$this->collection]))
            ->withLang(LanguageEnum::FRENCH)
            ->withKeywords([['ciel'], ['bleu']]) // triggers fulltext search
            ->withLimit(5)
            ->provide();

        $this->assertEquals(1, $results->count());

        /** @var Chunk $ciel */
        $ciel = $results->first();

        $this->assertEquals("Le ciel est bleu aujourd'hui.", $ciel->text);
        $this->assertEquals(0.3, $ciel->_score);
    }

    public function test_provide_extracts_chunks_and_embeddings_and_searches(): void
    {
        \Illuminate\Support\Facades\Cache::clear();

        $results = ChunksProvider::use()
            ->withCollections(collect([$this->collection]))
            ->withLang(LanguageEnum::FRENCH)
            ->withText('ciel bleu') // triggers vector search
            ->withKeywords([['ciel'], ['bleu']]) // triggers fulltext search
            ->withLimit(5)
            ->provide();

        $this->assertEquals(2, $results->count());

        /** @var Chunk $ciel */
        $ciel = $results->first();

        $this->assertEquals("Le ciel est bleu aujourd'hui.", $ciel->text);
        $this->assertEquals(1.0, $ciel->_score);

        /** @var Chunk $herbe */
        $herbe = $results->last();

        $this->assertEquals("L'herbe est verte en été.", $herbe->text);
        $this->assertEquals(0.0, $herbe->_score);
    }

    public function test_combine_generates_correct_combinations(): void
    {
        $provider = ChunksProvider::use();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('combine');
        $method->setAccessible(true);

        $input = [
            ['red', 'blue'],
            ['car', 'bike']
        ];

        $result = $method->invoke($provider, $input);

        $expected = [
            'red car',
            'red bike',
            'blue car',
            'blue bike'
        ];

        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function test_combine_with_sample_limit(): void
    {
        $provider = ChunksProvider::use();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('combine');
        $method->setAccessible(true);

        $input = [
            ['a', 'b', 'c'],
            ['1', '2', '3']
        ];

        $result = $method->invoke($provider, $input, 2);

        $this->assertCount(2, $result);
    }

    public function test_min_max_scaler_normalizes_scores(): void
    {
        $provider = ChunksProvider::use();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('minMaxScaler');
        $method->setAccessible(true);

        $chunk1 = new Chunk();
        $chunk1->_score = 10.0;
        $chunk2 = new Chunk();
        $chunk2->_score = 20.0;
        $chunk3 = new Chunk();
        $chunk3->_score = 30.0;

        $chunks = collect([$chunk1, $chunk2, $chunk3]);

        /** @var \Illuminate\Support\Collection $result */
        $result = $method->invoke($provider, $chunks);

        $this->assertEquals(0.0, $result[0]->_score);
        $this->assertEquals(0.5, $result[1]->_score);
        $this->assertEquals(1.0, $result[2]->_score);
    }

    public function test_min_max_scaler_handles_same_scores(): void
    {
        $provider = ChunksProvider::use();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('minMaxScaler');
        $method->setAccessible(true);

        $chunk1 = new Chunk();
        $chunk1->_score = 10.0;
        $chunk2 = new Chunk();
        $chunk2->_score = 10.0;

        $chunks = collect([$chunk1, $chunk2]);

        /** @var \Illuminate\Support\Collection $result */
        $result = $method->invoke($provider, $chunks);

        $this->assertEquals(1.0, $result[0]->_score);
        $this->assertEquals(1.0, $result[1]->_score);
    }
}
