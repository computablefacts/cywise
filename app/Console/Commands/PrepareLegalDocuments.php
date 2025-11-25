<?php

namespace App\Console\Commands;

use App\AgentSquad\Providers\EmbeddingsProvider;
use App\AgentSquad\Providers\LlmsProvider;
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
    protected $signature = 'legal:prepare {input} {output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a legal document to a list of chunks.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $in = $this->argument('input');
        $out = $this->argument('output');

        if (is_dir($in)) {
            $this->processDirectory($in, $out);
        } elseif (is_file($in)) {
            $this->processFile($in, $out);
        } else {
            throw new \Exception('Invalid input path : ' . $in);
        }
    }

    private function processDirectory(string $dir, string $output): void
    {
        $ffs = scandir($dir);

        unset($ffs[array_search('.', $ffs, true)]);
        unset($ffs[array_search('..', $ffs, true)]);

        if (count($ffs) < 1) {
            return;
        }
        foreach ($ffs as $ff) {
            if (is_dir($dir . '/' . $ff)) {
                $this->processDirectory($dir . '/' . $ff, $output);
            } else if (is_file($dir . '/' . $ff)) {
                $this->processFile($dir . '/' . $ff, $output);
            }
        }
    }

    private function processFile(string $file, string $output): void
    {
        if (!Str::endsWith($file, '.docx') && !Str::endsWith($file, '.doc')) {
            Log::warning("Skipping file $file : not a .doc or .docx file.");
            return;
        }

        $filename = Str::slug(basename($file));
        $rtf = "{$output}/{$filename}.rtf";
        $html = "{$output}/{$filename}.html";
        $txt = "{$output}/{$filename}.txt";
        $toc = "{$output}/{$filename}_toc.txt";
        $facts = "{$output}/{$filename}_facts.txt";

        if (!file_exists($rtf)) {
            $this->info("Converting {$filename} to RTF...");
            shell_exec("unoconv -f rtf -o \"{$rtf}\" \"{$file}\"");
            $this->info("File converted to RTF.");
        }
        if (!file_exists($rtf)) {
            $this->warn("Skipping file {$filename} : no RTF file.");
            return;
        }
        if (!file_exists($html)) {
            $this->info("Converting {$rtf} to HTML...");
            shell_exec("unrtf \"{$rtf}\" >\"{$html}\"");
            $this->info("File converted to HTML.");
        }
        if (!file_exists($html)) {
            $this->warn("Skipping file {$filename} : no HTML file.");
            return;
        }
        if (!file_exists($txt)) {
            $this->info("Converting {$html} to TXT...");
            shell_exec("lynx --dump \"{$html}\" >\"{$txt}\"");
            $this->info("File converted to TXT.");
        }
        if (!file_exists($txt)) {
            $this->warn("Skipping file {$filename} : no TXT file.");
            return;
        }
        if (!file_exists($toc)) {
            $this->info("Extracting table of contents from {$txt}...");
            $content = \File::get($txt);
            $answer = LlmsProvider::provide("
                Extrait de ce document en français tous les titres de sections et de sous-sections. 
                Remplace les noms de personnes par 'XXX', les noms de sociétés par 'YYY' et les noms de rues, de boulevards, d'avenues et de villes par 'ZZZ'.
                
                {$content}
            ", 'google/gemini-2.5-flash', 30 * 60);
            $sections = collect(explode("\n", $answer))
                ->filter(fn(string $line) => !empty($line))
                // ->filter(fn(string $line) => Str::startsWith(Str::trim($line), '* '))
                ->values()
                ->join("\n");
            \File::put($toc, $sections);
            $this->info("Table of contents extracted.");
        }
        if (!file_exists($toc)) {
            $this->warn("Skipping file {$filename} : no TOC file.");
            return;
        }
        if (!file_exists($facts)) {
            $this->info("Extracting facts from {$txt}...");
            $content = \File::get($txt);
            $answer = LlmsProvider::provide("
                Extrait les faits, les textes de lois ainsi que la jurisprudence utilisée pour chaque demande de la partie adverse du document de conclusions juridiques entre [CONCL] et [/CONCL]. 
                Remplace les noms de personnes par 'XXX', les noms de sociétés par 'YYY' et les noms de rues, de boulevards, d'avenues et de ville par 'ZZZ'.
                Formate la sortie sous forme d'un fichier markdown où :
                - Chaque titre de section est une demande de la partie adverse
                - Chaque section est divisée en quatre sous-sections :
                  - Une section de titre 'Conclusion' explicitant ce que cherche à prouver l'auteur des conclusions dans cette section, i.e. l'argument principal.
                  - Une section de titre 'Loi de passage' explicitant les liens sous-entendus ou explicites entre la conclusion et les faits.
                  - Une section de titre 'En droit' listant les textes de lois et la jurisprudence utilisés. Résume l'esprit du texte en une phrase.
                  - Une section de titre 'Au cas présent' listant les faits.
                
                [CONCL]
                {$content}
                [/CONCL]
            ", 'google/gemini-2.5-flash', 30 * 60);
            \File::put($facts, $answer);
            $this->info("Facts extracted.");
        }
        if (!file_exists($facts)) {
            $this->warn("Skipping file {$filename} : no FACTS file.");
            return;
        }

        $content = \File::get($facts);
        $lines = explode("\n", $content);
        $sections = [];
        $title = null;
        $subtitle = null;
        $section = [];

        foreach ($lines as $line) {

            $line = Str::trim($line);

            if (Str::StartsWith($line, '# ')) {
                if (!empty($title)) {
                    $sections[$title] = $section;
                }
                $title = $line;
                $subtitle = null;
                $section = [];
            } else if (Str::StartsWith($line, '## Conclusion')) {
                $subtitle = $line;
                $section[$subtitle] = [];
            } else if (Str::StartsWith($line, '## Loi de passage')) {
                $subtitle = $line;
                $section[$subtitle] = [];
            } else if (Str::StartsWith($line, '## En droit')) {
                $subtitle = $line;
                $section[$subtitle] = [];
            } else if (Str::StartsWith($line, '## Au cas présent')) {
                $subtitle = $line;
                $section[$subtitle] = [];
            } else if (!empty($line)) {
                $section[$subtitle][] = $line;
            }
        }
        if (!empty($title)) {
            $sections[$title] = $section;
        }

        // Log::debug($sections);

        $vectors = new FileVectorStore($output);

        foreach ($sections as $section => $subsections) {

            $vector = EmbeddingsProvider::provide($section, [$section => $subsections]);
            $vectors->addVector($vector);

            foreach ($subsections as $subsection => $lines) {
                foreach ($lines as $line) {
                    $vector = EmbeddingsProvider::provide($line, [$section => $subsections]);
                    $vectors->addVector($vector);
                }
            }
        }
    }
}
