<?php

namespace App\AgentSquad\Vectors;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileVectorStore extends AbstractVectorStore
{
    private string $directory;
    private string $name;
    private string $ext;

    public static function unpack(string $archive): string
    {
        $dirIn = database_path('seeders/vectors');
        $dirOut = storage_path('app/vectors');
        $dirname = Str::before($archive, '.zip');
        $output = "{$dirOut}/{$dirname}";

        if (file_exists($output) && is_dir($output)) {
            return $output;
        }
        if (!file_exists($dirOut)) {

            Log::debug("Creating directory '{$dirOut}'...");

            if (!mkdir($dirOut, 0755, true)) {
                throw new \Exception("Failed to create directory: {$dirOut}");
            }

            Log::debug("Directory '{$dirOut}' created.");
        }

        $input = "{$dirIn}/{$archive}";

        if (!Str::endsWith($input, '.enc')) {

            $output = "{$dirOut}/{$archive}";

            if (!file_exists($output)) {

                Log::debug("Copying file '{$input}'...");

                copy($input, "{$dirOut}/{$archive}");

                Log::debug("File '{$input}' copied to '{$output}'.");
            }
            $input = $output;
        } else {

            $filename = basename($input, '.enc');
            $output = "{$dirOut}/{$filename}";

            if (!file_exists($output)) {

                Log::debug("Decrypting file '{$input}'...");

                $file = cywise_decrypt_file(config('towerify.hasher.nonce'), $input);
                copy($file, $output);

                Log::debug("File '{$input}' decrypted to '{$output}'.");
            }
            $input = $output;
        }
        if (!Str::endsWith($input, '.zip')) {
            throw new \Exception("Invalid archive format: {$input}");
        }

        $dirname = basename($input, '.zip');
        $output = "{$dirOut}/{$dirname}";

        if (!file_exists($output) || !is_dir($output)) {
            $files = cywise_unpack_files($dirOut, '*.zip');
        }

        unlink($input);
        return $output;
    }

    public function __construct(string $directory, int $topK = 4, string $name = 'cywise', string $ext = '.vectors')
    {
        parent::__construct($topK);

        $this->directory = $directory;
        $this->name = $name;
        $this->ext = $ext;

        if (!is_dir($this->directory)) {
            throw new \Exception("Directory '{$this->directory}' does not exist");
        }
    }

    public function clear(): void
    {
        if (file_exists($this->storage())) {
            unlink($this->storage());
        }
    }

    /** @param Vector[] $vectors */
    public function addVectors(array $vectors): void
    {
        file_put_contents(
            $this->storage(),
            implode(PHP_EOL, array_map(fn(Vector $vector) => Vector::toString($vector), $vectors)) . PHP_EOL,
            FILE_APPEND
        );
    }

    protected function vectors(): \Generator
    {
        return $this->line($this->storage());
    }

    private function line(string $filename): \Generator
    {
        if (file_exists($filename)) {
            $f = fopen($filename, 'r');
            try {
                while ($line = fgets($f)) {
                    $vector = Vector::fromString($line);
                    if ($vector->isValid()) {
                        yield $vector;
                    }
                }
            } finally {
                fclose($f);
            }
        }
    }

    private function storage(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $this->name . $this->ext;
    }
}
