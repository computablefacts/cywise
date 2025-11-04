<?php

namespace App\Console\Commands;

use App\AgentSquad\Vectors\FileVectorStore;
use App\AgentSquad\Vectors\Vector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareAnssiDataset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anssi:prepare';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert some ANSSI documents from /database/seeders/frameworks/anssi to a list of chunks.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $in = database_path('seeders/frameworks/anssi');
        $out = database_path('seeders/vectors');

        if (file_exists($in) && is_dir($out)) {

            $vectors = new FileVectorStore($out);

            $this->processFile($vectors, "{$in}/anssi-guide-hygiene.2.jsonl.gz");
            $this->processFile($vectors, "{$in}/anssi-genai-security-recommendations-1.0.2.jsonl.gz");

            cywise_pack_files($out, '*.vectors', "anssi");
        }
    }

    private function processFile(FileVectorStore $vectors, string $in): void
    {
        $this->info("Processing {$in}...");

        $filename = basename($in, '.2.jsonl.gz');
        $handleIn = gzopen($in, 'rb');

        if (!$handleIn) {
            throw new \Exception("Could not open file {$in}");
        }

        $tmp = "/tmp/{$filename}.jsonl";
        $handleOut = fopen($tmp, 'wb');
        if (!$handleOut) {
            gzclose($handleIn);
        } else {
            while (!gzeof($handleIn)) {
                fwrite($handleOut, gzread($handleIn, 4096));
            }
            gzclose($handleIn);
            fclose($handleOut);
        }

        $this->info("File decompressed to {$tmp}");

        collect(explode("\n", File::get($tmp)))
            ->filter(fn(string $line) => !empty($line))
            ->map(fn(string $line) => json_decode($line, true))
            ->flatMap(fn(array $item) => collect($item['hypothetical_questions'])
                ->map(fn(array $question) => new Vector($question['question'], $question['embedding'], [
                    'answer' => $item['text'],
                    'source' => $filename,
                ]))
            )
            ->each(fn(Vector $vector) => $vectors->addVector($vector));
    }
}
