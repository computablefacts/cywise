<?php

namespace App\Console\Commands;

use App\AgentSquad\Providers\EmbeddingsProvider;
use App\AgentSquad\Vectors\FileVectorStore;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareRowdenDataset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rowden:prepare {input} {output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert https://huggingface.co/datasets/Rowden/CybersecurityQAA to a list of chunks.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $in = $this->argument('input');
        $out = $this->argument('output');
        $user = User::query()->where('email', config('towerify.admin.email'))->first();

        $user->actAs(); // otherwise the tenant will not be properly set

        if (file_exists($in) && is_dir($out)) {

            $vectors = new FileVectorStore($out);

            collect(json_decode(File::get($in), true))
                ->map(fn(array $item) => $item['vars'])
                ->filter(fn(array $item) => $item['reviewed_by_human'] === 'TRUE' && $item['reviewed_by_expert'] === 'TRUE')
                ->each(function (array $item) use ($vectors) {
                    $question = $item['question'];
                    $answer = $item['answer'];
                    $source = $item['sourceURL'];
                    $vector = EmbeddingsProvider::provide($question, [
                        'answer' => $answer,
                        'source' => $source,
                    ]);
                    $vectors->addVector($vector);
                });

            cywise_pack_files($out, '*.vectors', "rowden_cybersecurityqaa");
        }
    }
}
