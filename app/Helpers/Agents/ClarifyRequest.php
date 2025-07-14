<?php

namespace App\Helpers\Agents;

use App\Models\Chunk;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Str;

/** @deprecated */
class ClarifyRequest extends AbstractAction
{
    private string $message;

    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "clarify_request",
                "description" => "Ask user for clarification when request is unclear.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "question" => [
                            "type" => ["string"],
                            "description" => "Question to ask user for clarification.",
                        ],
                    ],
                    "required" => ["question"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct(User $user, string $threadId, array $messages, array $args = [], string $message = "I'm sorry, but I didn't understand your request. Could you please provide more details or rephrase your question?")
    {
        parent::__construct($user, $threadId, $messages, $args);
        $this->message = $message;
    }

    public function html(): string
    {
        return $this->enhanceHtmlAnswerWithSources($this->output);
    }

    public function text(): string
    {
        return $this->output;
    }

    public function markdown(): string
    {
        return $this->enhanceMarkdownAnswerWithSources($this->output);
    }

    function execute(): AbstractAction
    {
        $this->output = $this->args['question'] ?? $this->message;
        return $this;
    }

    private function enhanceHtmlAnswerWithSources(string $answer): string
    {
        $matches = [];
        // Extract: [12] from [[12]] or [[12] and [13]] from [[12],[13]]
        $isOk = preg_match_all("/\[\[\d+]]|\[\[\d+]|\[\d+]]/", $answer, $matches);
        if (!$isOk) {
            return Str::replace(["\n\n", "\n-"], "<br>", $answer);
        }
        $references = [];
        /** @var array $refs */
        $refs = $matches[0];
        foreach ($refs as $ref) {
            $id = Str::replace(['[', ']'], '', $ref);
            /** @var Chunk $chunk */
            $chunk = Chunk::find($id);
            /** @var File $file */
            $file = $chunk?->file()->first();
            $src = $file ? "<a href=\"{$file->downloadUrl()}\" style=\"text-decoration:none;color:black\">{$file->name_normalized}.{$file->extension}</a>, p. {$chunk?->page}" : "";
            if (Str::startsWith($chunk?->text ?? '', 'ESSENTIAL DIRECTIVE')) {
                $color = '#1DD288';
            } else if (Str::startsWith($chunk?->text ?? '', 'STANDARD DIRECTIVE')) {
                $color = '#C5C3C3';
            } else if (Str::startsWith($chunk?->text ?? '', 'ADVANCED DIRECTIVE')) {
                $color = '#FDC99D';
            } else {
                $color = '#F8B500';
            }
            $tt = $chunk?->text ?? '';
            $answer = Str::replace($ref, "<b style=\"color:{$color}\">[{$id}]</b>", $answer);
            $references[$id] = "
              <li style=\"padding:0;margin-bottom:0.25rem\">
                <b style=\"color:{$color}\">[{$id}]</b>&nbsp;
                <div class=\"cb-tooltip-list\">
                  {$src}
                  <span class=\"cb-tooltiptext cb-tooltip-list-top\" style=\"background-color:{$color};color:#444;\">
                    {$tt}
                  </span>
                </div>
              </li>
            ";
        }
        ksort($references);
        $answer = "{$answer}<br><br><b>Sources :</b><ul>" . collect($references)->values()->join("") . "</ul>";
        return Str::replace(["\n\n", "\n-"], "<br>", $answer);
    }

    private function enhanceMarkdownAnswerWithSources(string $answer): string
    {
        $matches = [];
        // Extract: [12] from [[12]] or [[12] and [13]] from [[12],[13]]
        $isOk = preg_match_all("/\[\[\d+]]|\[\[\d+]|\[\d+]]/", $answer, $matches);
        if (!$isOk) {
            return Str::replace(["\n\n", "\n-"], "<br>", $answer);
        }
        $references = [];
        /** @var array $refs */
        $refs = $matches[0];
        foreach ($refs as $ref) {
            $id = Str::replace(['[', ']'], '', $ref);
            /** @var Chunk $chunk */
            $chunk = Chunk::find($id);
            /** @var File $file */
            $file = $chunk?->file()->first();
            $src = $file ? "({$file->name_normalized}.{$file->extension})[{$file->downloadUrl()}], p. {$chunk?->page}" : "";
            $tt = $chunk?->text ?? '';
            $answer = Str::replace($ref, "**[{$id}]**", $answer);
            $references[$id] = "<li>**[{$id}]** {$src}: {$tt}</li>";
        }
        ksort($references);
        $answer = "{$answer}\n\n**Sources:**\n<ul>" . collect($references)->values()->join("") . "</ul>";
        return Str::replace(["\n\n", "\n-"], "\n", $answer);
    }
}
