<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\PromptsProvider;
use App\AgentSquad\ThoughtActionObservation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Parsedown;

class LabourLawyerWriter extends AbstractAction
{
    private string $model;

    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "labour_lawyer_writer",
                "description" => "Write the case's conclusions.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "question" => [
                            "type" => ["string"],
                            "description" => "The selected sections to rewrite.",
                        ],
                    ],
                    "required" => ["question"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct(string $model = 'Qwen/Qwen3-Next-80B-A3B-Thinking')
    {
        $this->model = $model;
    }

    public function isInvokable(): bool
    {
        return false;
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        $history = [];
        preg_match_all('/(\d+)\.(\d+)/', $input, $matches);

        foreach ($matches[0] as $i => $match) {

            $k = $matches[1][$i];
            $idx = $matches[2][$i];
            $item = \Cache::get("labour_lawyer_{$threadId}_{$user->id}_{$k}_{$idx}");

            if ($item != null) {
                $history[] = $item;
            }
        }

        // Log::debug($history);

        $chainOfThought = [];
        $conclusions = [];

        foreach ($history as $item) {

            $idx = $item['tgt_idx'];
            $doc = new LegalDocument($item['tgt_file']);
            $titre = strip_tags((new Parsedown)->text($item['tgt_txt']));
            $enDroit = strip_tags((new Parsedown)->text($doc->en_droit($idx)));
            $facts = \Cache::get("labour_lawyer_{$threadId}_{$user->id}_facts");
            $requests = \Cache::get("labour_lawyer_{$threadId}_{$user->id}_requests");
            $prompt = PromptsProvider::provide('default_consultations', [
                'TITRE' => $titre,
                'EN_DROIT' => $enDroit,
                'AU_CAS_PRESENT' => strip_tags((new Parsedown)->text($doc->au_cas_present($idx))),
                'FAITS' => $facts,
                'DEMANDES' => $requests,
            ]);

            // Log::debug($prompt);

            $answer = LlmsProvider::provide($prompt, $this->model, 120);
            $chainOfThought[] = new ThoughtActionObservation("I need to write about '{$item['src_txt']}'.", "Checking if '{$item['tgt_txt']}' from '{$item['tgt_file']}' is a good template...", strip_tags($answer));

            // Log::debug($answer);

            if (Str::contains($answer, "Le cas sélectionné n'est pas compatible avec le cas présent.")) {
                Log::debug("Skipping '{$item['tgt_txt']}' because it is not compatible with the current case.");
                continue;
            }

            $conclusions[] = Str::replace("En droit", "<br><br>**En droit**<br><br>",
                Str::replace("Au cas présent", "<br><br>**Au cas présent**<br><br>",
                    preg_replace('/^(.+)$/m', '**$1**<br><br>',
                        preg_replace("/\n{3,}/", "<br><br>",
                            Str::trim($answer)
                        ), 1
                    )
                )
            );
        }

        $conclusions = implode("<br><br>", $conclusions);

        if (empty(strip_tags($conclusions))) {
            return new FailedAnswer("Désolé ! Je n'ai pas trouvé de conclusions sur lesquelles me baser pour rédiger une réponse.", $chainOfThought);
        }
        return new SuccessfulAnswer($conclusions, $chainOfThought, true);
    }
}