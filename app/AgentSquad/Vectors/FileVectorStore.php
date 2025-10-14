<?php

namespace App\AgentSquad\Vectors;

class FileVectorStore extends AbstractVectorStore
{
    private string $directory;
    private string $name;
    private string $ext;

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
