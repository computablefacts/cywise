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
                    Answer questions related to the French labour law.
                    The action's input must always be in French, regardless of the user's language.
                ",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "question" => [
                            "type" => ["string"],
                            "description" => "The question to be asked to the labor lawyer.",
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
        $vector = EmbeddingsProvider::provide($input);
        $objets = $this->vectorStoreObjets->search($vector->embedding());
        $arguments = $this->vectorStoreArguments->search($vector->embedding());
        $results = array_merge(array_map(function (array $item): array {

            /** @var Vector $vector */
            $vector = $item['vector'];
            $idx = $vector->metadata('index_objet');
            $document = new LegalDocument("{$this->dir}/{$vector->metadata('file')}");

            return [
                'objet' => $document->objet($idx),
                'en_droit' => $document->en_droit($idx),
            ];
        }, $arguments), array_map(function (array $item): array {

            /** @var Vector $vector */
            $vector = $item['vector'];
            $idx = $vector->metadata('index_objet');
            $document = new LegalDocument("{$this->dir}/{$vector->metadata('file')}");

            return [
                'objet' => $document->objet($idx),
                'en_droit' => $document->en_droit($idx),
            ];
        }, $objets));

        $enDroit = implode("\n", array_unique(array_map(fn(array $item) => "[SECTION][TOPIC]{$item['objet']}[/TOPIC][LAW]{$item['en_droit']}[/LAW][/SECTION]", $results)));

        $prompt = "
Tu es un avocat en droit social.
Tu cherches à réaliser cette tâche : {$input}
Utilise le texte contenu entre les balises [EN_DROIT] et [/EN_DROIT] pour développer ton argumentaire:
- Les balises [EN_DROIT] et [/EN_DROIT] contiennent des sections thématiques placées entre [SECTION] et [/SECTION].
- Les balises [SECTION] et [/SECTION] contiennent une thématique entre [TOPIC] et [/TOPIC] ainsi que des citations de la loi se rapportant à cette thématique entre [LAW] et [/LAW].
Utilise toujours des citations de textes de loi pour appuyer ton argumentaire.
Anonymise les noms de personnes et de sociétés dans ton argumentaire.
N'utilise pas de markdown pour formuler ta réponse.

[EN_DROIT]
{$enDroit}
[/EN_DROIT]
        ";

        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => $prompt,
        ];
        $answer = LlmsProvider::provide($messages, $this->model, 30);
        array_pop($messages);
        return new SuccessfulAnswer($answer, [], !empty($answer));
    }
}