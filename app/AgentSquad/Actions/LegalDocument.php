<?php

namespace App\AgentSquad\Actions;

use Illuminate\Support\Facades\Log;

class LegalDocument
{
    private string $file;
    private ?array $objets;

    public function __construct(string $file)
    {
        $this->file = $file;
        $this->objets = null;

        if (!is_file($this->file)) {
            throw new \Exception("File '{$this->file}' does not exist");
        }
    }

    public function file(): string
    {
        return $this->file;
    }

    public function nbObjets(): int
    {
        return count($this->objets());
    }

    public function objet(int $index): string
    {
        return $this->objets()[$index]['objet'];
    }

    public function en_droit(int $index): string
    {
        return $this->objets()[$index]['en_droit'];
    }

    public function au_cas_present(int $index): string
    {
        return $this->objets()[$index]['au_cas_present'];
    }

    public function nbArguments(int $index): int
    {
        return count($this->argumentation($index));
    }

    public function argument(int $indexObjet, int $indexArgument): string
    {
        return $this->argumentation($indexObjet)[$indexArgument]['argument'];
    }

    public function faits(int $indexObjet, int $indexArgument): array
    {
        return $this->argumentation($indexObjet)[$indexArgument]['faits'];
    }

    private function argumentation(int $index): array
    {
        return $this->objets()[$index]['argumentation'];
    }

    private function objets(): array
    {
        if (!isset($this->objets)) {
            $this->objets = json_decode(file_get_contents($this->file), true);
        }
        if (!isset($this->objets)) {
            Log::error("$this->file is invalid");
        }
        return $this->objets;
    }
}
