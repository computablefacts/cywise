<?php

namespace App\Console\Commands;

use App\AgentSquad\Actions\LegalDocument;
use App\AgentSquad\Providers\EmbeddingsProvider;
use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Vectors\AbstractVectorStore;
use App\AgentSquad\Vectors\FileVectorStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PrepareLegalDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legal:prepare {input} {output} {prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a legal document to a list of chunks.';

    private AbstractVectorStore $vectorStoreObjets;
    private AbstractVectorStore $vectorStoreArguments;
    private string $model;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $in = $this->argument('input');
        $out = $this->argument('output');
        $prompt = $this->argument('prompt');

        $this->vectorStoreObjets = new FileVectorStore($out, 4, 'objets');
        $this->vectorStoreArguments = new FileVectorStore($out, 4, 'arguments');
        $this->model = 'google/gemini-2.5-pro';

        if (is_dir($in)) {
            $this->processDirectory($in, $out, $prompt);
        } elseif (is_file($in)) {
            $this->processFile($in, $in, $prompt);
        } else {
            throw new \Exception('Invalid input path : ' . $in);
        }

        $this->updateVectorDatabase($out);
    }

    private function processDirectory(string $dir, string $output, string $prompt): void
    {
        $ffs = scandir($dir);

        unset($ffs[array_search('.', $ffs, true)]);
        unset($ffs[array_search('..', $ffs, true)]);

        if (count($ffs) < 1) {
            return;
        }
        foreach ($ffs as $ff) {
            if (is_dir($dir . '/' . $ff)) {
                $this->processDirectory($dir . '/' . $ff, $output, $prompt);
            } else if (is_file($dir . '/' . $ff)) {
                $this->processFile($dir . '/' . $ff, $output, $prompt);
            }
        }
    }

    private function processFile(string $file, string $output, string $prompt): void
    {
        if (!Str::endsWith($file, '.docx') && !Str::endsWith($file, '.doc') && !Str::endsWith($file, '.pdf')) {
            Log::warning("Skipping file $file : not a .docx or .pdf file.");
            return;
        }

        $filename = Str::slug(basename($file));
        $md = "{$output}/{$filename}.md";
        $json = "{$output}/{$filename}.json";

        if (!file_exists($md)) {
            $this->info("Converting {$filename} to markdown...");
            if (Str::endsWith($file, '.pdf')) {
                shell_exec("pdftotext -layout \"$file\" - | pandoc -f markdown -t markdown_strict --extract-media=\"{$output}/attachments/{$filename}\" -o \"$md\"");
            } else {
                shell_exec("pandoc -t markdown_strict --extract-media=\"{$output}/attachments/{$filename}\" \"$file\" -o \"$md\"");
            }
            $this->info("File converted to markdown.");
        }
        if (!file_exists($md)) {
            $this->warn("Skipping file {$filename} : no markdown file.");
            return;
        }
        if (!file_exists($json)) {

            $this->info("Building JSON document from {$filename}...");

            $start = microtime(true);
            $markdown = file_get_contents($md);
            $prompt = \Illuminate\Support\Facades\File::get($prompt);
            $prompt = Str::replace('{DOC}', $markdown, $prompt);
            $answer = LlmsProvider::provide($prompt, $this->model, 30 * 60);
            $array = json_decode($answer, true);
            $end = microtime(true);
            $durationInSeconds = $end - $start;

            $this->info("JSON document built in {$durationInSeconds} seconds.");

            if ($array) {
                file_put_contents($json, json_encode($array, JSON_PRETTY_PRINT));
            }
        }
        if (!file_exists($json)) {
            $this->warn("Skipping file {$filename} : no json file.");
            return;
        }
    }

    private function updateVectorDatabase(string $output): void
    {
        $files = glob("{$output}/*.json");

        /** @var string $file */
        foreach ($files as $file) {

            $document = new LegalDocument($file);

            for ($i = 0; $i < $document->nbObjets(); $i++) {

                $metadata = [
                    'file' => $file,
                    'index_objet' => $i,
                ];

                if (empty($this->vectorStoreObjets->find($metadata))) {
                    $objet = $document->objet($i);
                    $vector = EmbeddingsProvider::provide($objet, $metadata);
                    $this->vectorStoreObjets->addVector($vector);
                }
                for ($j = 0; $j < $document->nbArguments($i); $j++) {

                    $metadata['index_argument'] = $j;

                    if (empty($this->vectorStoreArguments->find($metadata))) {
                        $argument = $document->argument($i, $j);
                        $vector = EmbeddingsProvider::provide($argument, $metadata);
                        $this->vectorStoreArguments->addVector($vector);
                    }
                }
            }
        }
    }

    /** @deprecated */
    private function objet(string $str): string
    {
        $prompt = "
            Ta réponse devra être en plein texte sans markdown.
            Retourne uniquement l'objet de cette phrase en enlevant notamment les noms de personnes et de sociétés :
            
            {$str}
        ";
        return LlmsProvider::provide($prompt, $this->model, 30 * 60);
    }
}
