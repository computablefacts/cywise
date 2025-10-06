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
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Parsedown;

class LabourLawyer extends AbstractAction
{
    private AbstractVectorStore $vectorStoreObjets;
    private AbstractVectorStore $vectorStoreArguments;
    private string $dir;
    private string $model;

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

    public function __construct(string $in, string $model = 'Qwen/Qwen3-Next-80B-A3B-Thinking')
    {
        $this->vectorStoreObjets = new FileVectorStore($in, 5, 'objets');
        $this->vectorStoreArguments = new FileVectorStore($in, 5, 'arguments');
        $this->dir = $in;
        $this->model = $model;
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => "
                Tu es un avocat en droit social. Tu défends l'employeur et non le salarié. 
                Voici ta tâche :
                - Extrait des données d'entrée du cas présent les contestations et demandes de la partie adverse en écrivant une contestation ou demande par ligne préfixée par 'd:'
                - Extrait des données d'entrée du cas présent les faits en écrivant un fait par ligne préfixée par 'f:'
                - N'écrit rien d'autre dans ta réponse que les contestations, les demandes et les faits.

                Voici un premier exemple de données d'entrée entre [IN] et [/IN] : 
                
                [IN]
                je dois rédiger des conclusions sur un salarié ayant été licencié pour inaptitude après un avis médical 
                rendu par le médecin du travail ; l'inaptitude est d'origine non professionnelle ; il conteste son 
                licenciement en précisant que la recherche de reclassement n'a pas été effectuée correctement dans le 
                Groupe dont fait partie l'entreprise qui l'emploie ; il demande des dommages et intérêts pour 
                licenciement sans cause réelle et sérieuse de 9 mois de salaire ; il a une ancienneté de 10 ans
                [/IN]
                
                Voici un exemple de sortie attendue pour ce premier exemple entre [OUT] et [/OUT] :
                
                [OUT]
                d:le salarié conteste son licenciement en précisant que la recherche de reclassement n'a pas été effectuée correctement dans le Groupe dont fait partie l'entreprise qui l'emploie
                d:le salarié demande des dommages et intérêts pour licenciement sans cause réelle et sérieuse de 9 mois de salaire
                f:le salarié a été licencié pour inaptitude après un avis médical rendu par le médecin du travail
                f:l'inaptitude du salarié est d'origine non professionnelle
                f:le salarié a une ancienneté de 10 ans
                [/OUT]
                
                Voici un second exemple de données d'entrée entre [IN] et [/IN] : 
                
                [IN]
                je dois rédiger des conclusions sur un salarié ayant été licencié pour inaptitude après un avis médical 
                rendu par le médecin du travail ; l'inaptitude est d'origine professionnelle ; il conteste son licenciement 
                en précisant que le harcèlement dont il se dit victime serait à l'origine de son inaptitude ; il ajoute 
                par ailleurs que la société n'aurait pas consulté le CSE, ce qu'elle avait l'obligation de faire ; il 
                demande  des dommages et intérêts pour licenciement sans cause réelle et sérieuse, à hauteur de 9 mois 
                de salaire alors qu'il a 4 ans d'ancienneté, ainsi que des dommages et intérêts pour réparer le harcèlement 
                à hauteur de 6 mois de salaire ;
                [/IN]
                
                Voici un exemple de sortie attendue pour ce second exemple entre [OUT] et [/OUT] :
                
                [OUT]
                d:le salarié conteste son licenciement en affirmant que le harcèlement dont il se dit victime serait à l'origine de son inaptitude
                d:le salarié conteste l'absence de consultation du CSE par la société, qu'il estime obligatoire
                d:le salarié demande des dommages et intérêts pour licenciement sans cause réelle et sérieuse, à hauteur de 9 mois de salaire
                d:le salarié demande des dommages et intérêts pour réparer le harcèlement, à hauteur de 6 mois de salaire
                f:le salarié a été licencié pour inaptitude après un avis médical rendu par le médecin du travail
                f:l'inaptitude du salarié est d'origine professionnelle
                f:le salarié a une ancienneté de 4 ans
                [/OUT]
                
                Voici les données d'entrée du cas présent : {$input}
            ",
        ];
        $answer = LlmsProvider::provide($messages, $this->model, 120);
        array_pop($messages);

        $demandes = [];
        $faits = [];

        foreach (explode("\n", $answer) as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'd:')) {
                $demandes[] = substr($line, 2);
            } elseif (str_starts_with($line, 'f:')) {
                $faits[] = substr($line, 2);
            }
        }

        $facts = "- " . implode("\n- ", $faits);
        $requests = "- " . implode("\n- ", $demandes);

        \Cache::put("labour_lawyer_{$threadId}_{$user->id}_facts", $facts, 60 * 5);
        \Cache::put("labour_lawyer_{$threadId}_{$user->id}_requests", $requests, 60 * 5);

        Log::debug("FAITS : ", $faits);
        Log::debug("DEMANDES : ", $demandes);

        $history = collect($demandes)
            ->concat($faits)
            ->flatMap(function (string $txt) {

                $vector = EmbeddingsProvider::provide($txt);
                $documents = $this->vectorStoreObjets->search($vector->embedding());

                return array_map(function (array $item) use ($txt): array {

                    /** @var Vector $vector */
                    $vector = $item['vector'];
                    $idx = $vector->metadata('index_objet');
                    $document = new LegalDocument("{$this->dir}/{$vector->metadata('file')}");

                    return [
                        'src_txt' => $txt,
                        'tgt_txt' => $document->objet($idx),
                        'tgt_score' => $item['similarity'],
                        'tgt_file' => "{$this->dir}/{$vector->metadata('file')}",
                        'tgt_idx' => $idx,
                    ];
                }, $documents);
            })
            ->filter(fn(array $item) => $item['tgt_score'] >= 0.6)
            ->sortByDesc('tgt_score')
            ->values()
            ->toArray();

        Log::debug($history);

        $chainOfThought = [];
        $conclusions = [];
        $sections = [];
        $thinking = [];

        for ($k = 0; $k < count($history); $k++) {

            $item = $history[$k];

            /* if (in_array($item['src_txt'], $sections)) {
                Log::debug("Skipping '{$item['tgt_txt']}' because it's already in a section.");
                continue;
            } */

            $idx = $item['tgt_idx'];
            $doc = new LegalDocument($item['tgt_file']);
            $titre = strip_tags((new Parsedown)->text($item['tgt_txt']));
            $enDroit = strip_tags((new Parsedown)->text($doc->en_droit($idx)));
            $auCasPresent = "";

            \Cache::put("labour_lawyer_{$threadId}_{$user->id}_{$k}_{$idx}", $item, 60 * 5);

            for ($i = 0; $i < $doc->nbArguments($idx); $i++) {

                $file = Str::afterLast($doc->file(), '/');
                $auCasPresent .= ($i === 0 ? "<b>[<span style=\"color:#ffaa00\">{$k}.{$idx}</span>] {$titre} (<i>{$file}</i>)</b><br><br><ul>" : "");
                $auCasPresent .= ("<li><b>Argument.</b> " . $doc->argument($idx, $i) . "<ul>");
                $factz = $doc->faits($idx, $i);

                for ($j = 0; $j < count($factz); $j++) {
                    $auCasPresent .= ("<li><b>Fait.</b> " . $factz[$j] . "</li>");
                }
                $auCasPresent .= "</ul></li><br>";
                $auCasPresent .= ($i === $doc->nbArguments($idx) - 1 ? "</ul>" : "");
            }

            $auCasPresent = Str::trim($auCasPresent);
            $thinking[] = $auCasPresent;
            Log::debug($auCasPresent);

            /* $prompt = PromptsProvider::provide('default_consultations', [
                'TITRE' => $titre,
                'EN_DROIT' => $enDroit,
                'AU_CAS_PRESENT' => strip_tags((new Parsedown)->text($doc->au_cas_present($idx))),
                'FAITS' => $facts,
                'DEMANDES' => $requests,
            ]);

            // Log::debug($prompt);

            $answer = LlmsProvider::provide($prompt, $this->model, 120);
            $chainOfThought[] = new ThoughtActionObservation("I need to write about '{$item['src_txt']}'.", "Checking if '{$item['tgt_txt']}' from '{$item['tgt_file']}' is a good template...", strip_tags($answer));

            Log::debug($answer);

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
            $sections[] = $item['src_txt']; */
        }
        return new SuccessfulAnswer('labour_lawyer', implode("", $thinking), [], true);

        // Log::debug($conclusions);

        $conclusions = implode("<br><br>", $conclusions);

        if (empty(strip_tags($conclusions))) {
            return new FailedAnswer('labour_lawyer', "Désolé ! Je n'ai pas trouvé de conclusions sur lesquelles me baser pour rédiger une réponse.", $chainOfThought);
        }
        return new SuccessfulAnswer('labour_lawyer', $conclusions, $chainOfThought, true);
    }
}