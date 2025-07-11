<?php

namespace App\Console\Commands;

use App\AgentSquad\Providers\HypotheticalQuestionsProvider;
use App\Models\User;
use App\Models\YnhFramework;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class PrepareFramework extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'framework:prepare {input} {output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a Cyber framework to a list of chunks.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $in = $this->argument('input');
        $out = $this->argument('output');
        $user = User::query()->where('email', config('towerify.admin.email'))->first();

        Auth::login($user);

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
        if (!Str::endsWith($file, '.yaml')) {
            return;
        }

        $yaml = File::get($file);
        $json = Yaml::parse($yaml);

        if (!isset($json['objects']['framework'])) {
            return;
        }

        $infos = [
            'locale' => $json['locale'],
            'name' => $json['name'],
            'description' => $json['description'],
            'copyright' => $json['copyright'],
            'version' => $json['version'],
            'provider' => $json['provider'] === 'EUROPEAN COMMISSION' ? 'EU' : ($json['provider'] === 'Gouvernement français' ? 'FR' : ($json['provider'] === 'GDPR.EU' ? 'EU' : $json['provider'])),
            'file' => Str::replace('.yaml', '.jsonl', basename($file)),
        ];

        file_put_contents($output . Str::replace('.yaml', '.json', basename($file)), json_encode($infos) . PHP_EOL, FILE_APPEND);

        $framework = $json['objects']['framework'];
        $requirements = $framework['requirement_nodes'];
        $tree = [];
        $urnToNodeMap = [];

        foreach ($requirements as $node) {
            $urn = $node['urn'];
            $urnToNodeMap[$urn] = array_merge($node, ['children' => []]);
        }
        foreach ($requirements as $node) {
            if (empty($node['parent_urn'])) {
                $tree[] = &$urnToNodeMap[$node['urn']];
            } else {
                $parentUrn = $node['parent_urn'];
                if (isset($urnToNodeMap[$parentUrn])) {
                    $urnToNodeMap[$parentUrn]['children'][] = &$urnToNodeMap[$node['urn']];
                }
            }
        }

        $filename = $output . Str::replace('.yaml', '.jsonl', basename($file));

        foreach ($tree as $node) {

            $chunks = [];
            $this->generateChunk($node, [], $chunks);

            foreach ($chunks as $chunk) {
                file_put_contents($filename, json_encode($chunk) . PHP_EOL, FILE_APPEND);
            }
        }
        if (file_exists($filename)) {

            $filename = $output . Str::replace('.yaml', '.2.jsonl', basename($file));
            $framework = new YnhFramework();
            $framework->fill($infos);
            $framework->file = Str::after($output, '/database/') . $framework->file;

            foreach ($framework->blocks() as $block) {

                $block = Str::trim($block);
                $chunk = [
                    'page' => 1,
                    'tags' => $this->extractTitlesFromMarkdown($block),
                    // 'text' => "**Provider.** {$framework->provider}\n**Title.** {$framework->name}\n**Description.** {$framework->description}\n\n" . trim($block),
                    'text' => $block,
                    'hypothetical_questions' => HypotheticalQuestionsProvider::provide($framework->locale, $block),
                ];

                file_put_contents($filename, json_encode($chunk) . PHP_EOL, FILE_APPEND);
            }
        }
    }

    private function generateChunk(array $node, array $parentTags, array &$chunks): void
    {
        if (isset($node['name'])) {
            $currentTags = array_merge($parentTags, [trim($node['name'])]);
        } else {
            $currentTags = $parentTags;
        }
        if (empty($node['children'])) {
            $chunks[] = [
                'page' => 1,
                'tags' => $currentTags,
                'text' => trim($node['description'] ?? 'No description available'),
            ];
        } else {
            foreach ($node['children'] as $childNode) {
                $this->generateChunk($childNode, $currentTags, $chunks);
            }
        }
    }

    private function extractTitlesFromMarkdown(string $markdown): array
    {
        $titles = [];
        $lines = explode("\n", $markdown);

        foreach ($lines as $line) {
            if (preg_match('/^#{1,6}\s+(.+)$/', trim($line), $matches)) {
                $titles[] = trim($matches[1]);
            }
        }
        return $titles;
    }

    private function removeTitlesFromMarkdown(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        return implode("\n", array_filter($lines, fn(string $line) => !preg_match('/^#{1,6}\s+(.+)$/', trim($line))));
    }
}
