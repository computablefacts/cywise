<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
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

    public function __construct(string $in, string $model = 'deepseek-ai/DeepSeek-R1-0528-Turbo')
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

                Voici un exemple de données d'entrée entre [IN] et [/IN] : 
                
                [IN]
                je dois rédiger des conclusions sur un salarié ayant été licencié pour inaptitude après un avis médical 
                rendu par le médecin du travail ; l'inaptitude est d'origine non professionnelle ; il conteste son 
                licenciement en précisant que la recherche de reclassement n'a pas été effectuée correctement dans le 
                Groupe dont fait partie l'entreprise qui l'emploie ; il demande des dommages et intérêts pour 
                licenciement sans cause réelle et sérieuse de 9 mois de salaire ; il a une ancienneté de 10 ans
                [/IN]
                
                Voici un exemple de sortie attendue entre [OUT] et [/OUT] :
                
                [OUT]
                d:le salarié conteste son licenciement en précisant que la recherche de reclassement n'a pas été effectuée correctement dans le Groupe dont fait partie l'entreprise qui l'emploie
                d:le salarié demande des dommages et intérêts pour licenciement sans cause réelle et sérieuse de 9 mois de salaire
                f:le salarié a été licencié pour inaptitude après un avis médical rendu par le médecin du travail
                f:l'inaptitude du salarié est d'origine non professionnelle
                f:le salarié a une ancienneté de 10 ans
                [/OUT]
                
                Voici les données d'entrée du cas présent : {$input}
            ",
        ];
        $answer = LlmsProvider::provide($messages, $this->model);
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

        $context = collect($demandes)
            ->concat($faits)
            ->flatMap(function (string $txt) {

                $vector = EmbeddingsProvider::provide($txt);
                $documents = $this->vectorStoreObjets->search($vector->embedding());
                return array_map(function (array $item) use ($txt): array {

                    /** @var Vector $vector */
                    $vector = $item['vector'];
                    $idx = $vector->metadata('index_objet');
                    $document = new LegalDocument("{$this->dir}/{$vector->metadata('file')}");

                    $arguments = [];

                    for ($i = 0; $i < $document->nbArguments($idx); $i++) {
                        $arguments[] = $document->argument($idx, $i);
                    }

                    $result = [
                        'uuid' => Str::uuid()->toString(),
                        'src_txt' => $txt,
                        'tgt_txt' => $document->objet($idx),
                        'tgt_score' => $item['similarity'],
                        'tgt_args' => $arguments,
                        'tgt_file' => "{$this->dir}/{$vector->metadata('file')}",
                        'tgt_idx' => $idx,
                    ];

                    // Cache::put($result['uuid'], $result, now()->addMinutes(60));

                    return $result;
                }, $documents);
            })
            ->sortByDesc('tgt_score')
            ->values()
            ->toArray();

        Log::debug($context);

        $conclusions = collect($context)
            ->map(function (array $item) use (&$conclusions) {
                $doc = new LegalDocument($item['tgt_file']);
                $titre = strip_tags((new Parsedown)->text($item['tgt_txt']));
                $enDroit = strip_tags((new Parsedown)->text($doc->en_droit($item['tgt_idx'])));
                $auCasPresent = strip_tags((new Parsedown)->text($doc->au_cas_present($item['tgt_idx'])));
                return "Titre :\n\n{$titre}\n\nEn droit :\n\n{$enDroit}\n\nAu cas présent :\n\n{$auCasPresent}";
            })
            ->join("\n\n[EXISTANT][/EXISTANT]\n\n");

        $prompt = "
En droit français, les conclusions sont des actes de procédure écrits rédigés par les avocats (ou les parties elles-mêmes 
en l'absence d'avocat) pour exposer leurs prétentions (demandes) et leurs moyens (arguments juridiques et factuels) devant 
un tribunal.

Tu es un avocat en droit social. Tu défends l'employeur. Ton objectif est de rédiger des conclusions.

Je te fournis ci-dessous :
- Des extraits de conclusions juridiques existantes entre [EXISTANT] et [/EXISTANT] (certains extraits peuvent être obsolètes, trop génériques ou inadaptés à mon cas).
- Le contexte factuel et juridique de mon cas présent entre [CONTEXTE_ACTUEL] et [/CONTEXTE_ACTUEL].

Extrait les conclusions entre [EXISTANT] et [/EXISTANT] les plus adaptées au cas présent et renvoie les moi sans les modifier.
Pour chaque conclusion renvoyée justifie ton choix et notamment les limites d'applicabilité au cas présent.

[EXISTANT]
{$conclusions}
[/EXISTANT]

[CONTEXTE_ACTUEL]
{$input}
[/CONTEXTE_ACTUEL]
        ";

        Log::debug($prompt);

        $answer = LlmsProvider::provide($answer, $this->model);
        return new SuccessfulAnswer($answer, [], !empty($answer));
    }
}