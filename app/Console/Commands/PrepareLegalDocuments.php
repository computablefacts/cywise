<?php

namespace App\Console\Commands;

use App\AgentSquad\Providers\LlmsProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

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
        $html = "{$output}/{$filename}.html";
        $json = "{$output}/{$filename}.json";
        $doc = [];

        if (file_exists($json)) {
            $doc = json_decode(file_get_contents($json));
        } else {

            if (!file_exists($html)) {
                $this->info("Converting {$filename} to HTML...");
                shell_exec("unoconv -o \"{$html}\" \"{$file}\"");
                $this->info("File converted to HTML.");
            }
            if (!file_exists($html)) {
                $this->warn("Skipping file {$filename} : no HTML file.");
                return;
            }

            $crawler = new Crawler(file_get_contents($html));

            foreach ($crawler as $domElement) {
                $doc = array_merge($doc, $this->parse($domElement));
            }

            $result = [];

            foreach ($doc as $cur) {

                $key = key($cur);
                $value = current($cur);

                if (empty($result) || $key === 's' || $key === 'ss' || $key === 'c') {
                    $result[] = $cur;
                } else {

                    $last = $result[count($result) - 1];
                    $lastKey = key($last);
                    $lastValue = current($last);

                    if ($key === $lastKey) {
                        $result[count($result) - 1] = [$key => array_merge($lastValue, $value)];
                    } else {
                        $result[] = $cur;
                    }
                }
            }

            $blocks = $this->blocks($result);
            $kb = $this->knowledgeBase(implode("\n\n", $blocks));

            file_put_contents($json, json_encode([
                'kb' => $kb,
                'doc' => $result,
            ], JSON_PRETTY_PRINT));

            shell_exec("find \"{$output}\" -type f ! -name \"*.json\" -delete");
        }
    }

    private function knowledgeBase(string $conclusions): array
    {
        $prompt = \Illuminate\Support\Facades\File::get(base_path("/app/Console/Commands/prompt_structure_conclusions.txt"));
        $prompt = Str::replace('{CONCLUSIONS}', $conclusions, $prompt);
        // Log::debug($prompt);
        $answer = LlmsProvider::provide($prompt, 'google/gemini-2.5-flash', 30 * 60);
        // Log::debug($answer);
        $matches = null;
        preg_match_all('/(?:```json\s*)?(.*)(?:\s*```)?/s', $answer, $matches);
        $answer = '[' . Str::after(Str::beforeLast(Str::trim($matches[1][0]), ']'), '[') . ']'; //  deal with "]<｜end▁of▁sentence｜>"
        $json = json_decode($answer, true) ?? [];
        Log::debug($json);
        return $json;
    }

    private function blocks(array $doc): array
    {
        $block = '';
        $blocks = [];

        foreach ($doc as $cur) {

            $key = key($cur);
            $value = current($cur);

            if ($key === 'p') {
                $block .= implode("\n\n", $value) . "\n\n";
            } else if ($key === 'c') {
                $block .= "> " . implode("\n> ", $value) . "\n\n";
            } else if ($key === 'ss') {
                $block .= "## " . Str::upper(implode(" ", $value)) . "\n\n";
            } else if (empty($block)) {
                $block .= "# " . Str::upper(implode(" ", $value)) . "\n\n";
            } else {
                $blocks[] = $block;
                $block = "# " . Str::upper(implode(" ", $value)) . "\n\n";
            }
        }
        if (!empty($block)) {
            $blocks[] = $block;
        }
        return $blocks;
    }

    private function parse(\DOMNode $node): array
    {
        $text = [];
        $citation = [];
        /** @var \DOMNode $childNode */
        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeName === 'p') {

                $paragraph = $this->trim($childNode->nodeValue);
                $hasLeftMargin = false;

                if ($childNode instanceof \DOMElement && $childNode->hasAttribute('style')) {
                    $style = $childNode->getAttribute('style');
                    preg_match('/margin-left:\s*([^;]+)/', $style, $matches);
                    $hasLeftMargin = !empty($matches[1]);
                }
                if (preg_match('/^\s*en\s+droit/i', $paragraph, $matches)) {
                    $text[] = ['ss' => [$paragraph]];
                } else if (preg_match('/^\s*au\s+cas\s+pr/i', $paragraph, $matches)) {
                    $text[] = ['ss' => [$paragraph]];
                } else if (Str::startsWith($paragraph, ['I.', 'II.', 'III.', 'IV.', 'V.', 'VI.', 'VII.', 'VIII.', 'IX.', 'X.',])) {
                    if (Str::startsWith(Str::after($paragraph, '.'), ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'])) { // subsection
                        $text[] = ['ss' => [$paragraph]];
                    } else { // section
                        $text[] = ['s' => [$paragraph]];
                    }
                } else if (Str::startsWith($paragraph, '«') && Str::contains($paragraph, '»')) { // citation on a single line
                    $text[] = ['c' => [$paragraph]];
                } else if (Str::startsWith($paragraph, '«') && !Str::contains($paragraph, '»')) { // citation begins
                    $citation[] = $paragraph;
                } else if (!empty($citation)) { // we are building a multilines citation
                    $citation[] = $paragraph;
                    if (Str::contains($paragraph, '»') && !Str::contains($paragraph, '«')) { // citation ends
                        $text[] = ['c' => $citation];
                        $citation = [];
                    } elseif (!$hasLeftMargin) { // fail gracefully: citation with missing ending
                        $text[] = ['c' => $citation];
                        $citation = [];
                    }
                } else if (!empty($paragraph)) {
                    $text[] = ['p' => [$paragraph]];
                }
            } else if (!empty($citation)) { // enumeration in citation
                $citation = array_merge($citation, array_merge(...array_map(fn($item) => $item['p'], $this->parse($childNode))));
            } else {
                $text = array_merge($text, $this->parse($childNode));
            }
        }
        return $text;
    }

    private function trim(string $str): string
    {
        return Str::trim(preg_replace(['/\t+/', '/\n/'], [' ', ' '], $str));
    }
}
