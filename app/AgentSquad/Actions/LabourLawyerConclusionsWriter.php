<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\Providers\EmbeddingsProvider;
use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Vectors\AbstractVectorStore;
use App\AgentSquad\Vectors\FileVectorStore;
use App\AgentSquad\Vectors\Vector;
use App\Models\User;

class LabourLawyerConclusionsWriter extends AbstractAction
{
    private AbstractVectorStore $vectorStore;
    private string $dir;

    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "labour_lawyer",
                "description" => "
                    Write conclusions (formal written pleadings through which a party (or their lawyer) sets out their 
                    claims (what they are asking the court to grant) and the grounds (factual and legal arguments) 
                    supporting those claims) related to the French labour law.
                    The action's input must always be the original user's input. 
                    The action's input must always be in French, regardless of the user's language.
                ",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "question" => [
                            "type" => ["string"],
                            "description" => "The factual arguments to be used in the conclusion and how to use them.",
                        ],
                    ],
                    "required" => ["question"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct(string $in)
    {
        $this->vectorStore = new FileVectorStore($in, 5);
        $this->dir = $in;
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        // Build a table of contents from the context
        $tocs = \File::get("{$this->dir}/tocs.txt");
        $prompt = "
            En te basant sur les exemples (entre [TOCS] et [/TOCS]) de tables des matières (entre [TOC] et [/TOC]) propose moi une table des matières pour le contexte (entre [CTX] et [/CTX]) ci-dessous.
            Renvoie uniquement la table des matières sans commentaires additionnels.
            
            [CTX]{$input}[/CTX]
            {$tocs}
        ";
        $answer = LlmsProvider::provide($prompt, 'google/gemini-2.5-flash', 30 * 60);

        // Find similar arguments in the historical data and generate a list of arguments for each entry of the table of contents
        $vector = EmbeddingsProvider::provide($input);
        $sections = array_unique(array_map(function (array $vector) {

            /** @var Vector $vec */
            $vec = $vector['vector'];
            /** @var array $metadata */
            $metadata = $vec->jsonSerialize()['metadata'];

            $text = '';

            foreach ($metadata as $section => $subsections) {

                $text .= "{$section}\n\n";

                foreach ($subsections as $subsection => $lines) {
                    $text .= ("{$subsection}\n\n" . implode("\n", $lines) . "\n\n");
                }
            }
            return $text;
        }, $this->vectorStore->search($vector->embedding())));
        $arguments = "[ARGS]\n" . implode("\n", array_map(fn(string $section) => "[ARG]\n{$section}\n[/ARG]", $sections)) . "\n[/ARGS]";
        $prompt = "
            En te basant sur les exemples (entre [ARGS] et [/ARGS]) d'argumentaires propose pour chaque entrée de la table des matières (entre [TOC] et [/TOC]) un argumentaire tenant compte du contexte (entre [CTX] et [/CTX]) ci-dessous.
            Lorsque tu réutilises un argument, vérifie que les faits du contexte suffisent pour rendre celui-ci opérant.
            N'invente pas de textes de lois ni de jurisprudences.
            Renvoie uniquement la table des matières augmentée de ton argumentaire détaillé.

            [CTX]{$input}[/CTX]
            [TOC]{$answer}[/TOC]
            {$arguments}
        ";
        $answer = LlmsProvider::provide($prompt, 'google/gemini-2.5-flash', 30 * 60);
        $answer = collect(explode("\n", $answer))
            ->map(fn(string $line) => "{$line}\n")
            ->values()
            ->join("\n");

        \Log::debug($answer);

        if (!empty($answer)) {
            return new SuccessfulAnswer($answer, [], true);
        }
        return new FailedAnswer("Désolé ! Je n'ai pas trouvé de conclusions sur lesquelles me baser pour rédiger une réponse.");
    }
}