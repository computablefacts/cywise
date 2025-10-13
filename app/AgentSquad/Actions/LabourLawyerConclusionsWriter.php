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
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Str;

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
        $answer = LlmsProvider::provide($messages, 'google/gemini-2.5-flash', 120);
        array_pop($messages);

        $pretentions = [];
        $faits = [];

        foreach (explode("\n", $answer) as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'd:')) {
                $pretentions[] = substr($line, 2);
            } elseif (str_starts_with($line, 'f:')) {
                $faits[] = substr($line, 2);
            }
        }

        $conclusions = collect($pretentions)
            ->concat($faits)
            ->flatMap(function (string $pretention) {

                $vector = EmbeddingsProvider::provide($pretention);
                $vectors = $this->vectorStore->search($vector->embedding());
                $pretentions = array_map(function (array $vector) {
                    $vector['file'] = $vector['vector']->metadata('file');
                    $vector['pretention'] = $vector['vector']->metadata('pretention');
                    $vector['majeure'] = $vector['vector']->metadata('majeure');
                    $vector['mineure'] = $vector['vector']->metadata('mineure');
                    $vector['conclusion'] = $vector['vector']->metadata('conclusion');
                    unset($vector['vector']);
                    return $vector;
                }, $vectors);

                return $pretentions;
            })
            ->map(function (array $p) {

                $pretention = Str::upper($p['pretention'] ?? '');
                $majeure = $p['majeure'] ?? '';
                $mineure = $p['mineure'] ?? '';
                $conclusion = $p['conclusion'] ?? '';

                return "[PRETENTION]# {$pretention}\n\n## En droit\n\n{$majeure}\n\n## Au cas présent\n\n{$mineure}\n\n## Conclusion\n\n{$conclusion}[/PRETENTION]";
            })
            ->join("");

        $prompt = file_get_contents(app_path('Console/Commands/prompt_write_conclusions.txt'));
        $prompt = Str::replace('{PRETENTIONS}', $conclusions, $prompt);
        $prompt = Str::replace('{AU_CAS_PRESENT}', $input, $prompt);
        \Log::debug($prompt);
        $answer = LlmsProvider::provide($prompt, 'google/gemini-2.5-flash', 2 * 60);

        if (!empty($answer)) {
            return new SuccessfulAnswer($answer, [], true);
        }
        return new FailedAnswer("Désolé ! Je n'ai pas trouvé de conclusions sur lesquelles me baser pour rédiger une réponse.");
    }
}