<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\Models\User;
use Illuminate\Support\Str;

class ToggleUserGetsAuditReport extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "toggle_user_gets_audit_report",
                "description" => "
                    Active/désactive (toggle) le flag gets_audit_report pour un ou plusieurs utilisateurs.
                    Fournir une liste d'emails d'utilisateurs séparés par des virgules.
                    Seuls les utilisateurs du même tenant que l'utilisateur courant peuvent être modifiés.
                    Exemples d'input:
                    - if the request is 'envoie une copie du rapport à alice@example.com', the input should be 'enable:alice@example.com'
                    - if the request is 'arrête d'envoyer des emails à alice@example.com et bob@example.com', the input should be 'disable:alice@example.com,bob@example.com'
                    - if the request is 'arrête de m'envoyer des emails', the input should be 'disable:me'
                    - if the request is 'réactive l'envoie des notifications', the input should be 'enable:me'
                    - if the request is 'active les emails pour tous les utilisateurs', the input should be 'enable:all'
                    - if the request is 'active les emails pour tous', the input should be 'enable:all'
                    - if the request is 'désactive les notifications pour tous les utilisateurs', the input should be 'disable:all'
                ",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "input" => [
                            "type" => "string",
                            "description" => "Liste séparée par des virgules d'emails d'utilisateur.",
                        ],
                    ],
                    "required" => ["input"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        $action = Str::trim(Str::before($input, ':'));
        $emails = collect(explode(',', Str::trim(Str::after($input, ':'))))
            ->map(fn(string $email) => Str::trim($email))
            ->filter(fn(string $email) => !empty($email) && ($email === 'me' || $email === 'all' || filter_var($email, FILTER_VALIDATE_EMAIL)))
            ->unique();

        if (!in_array($action, ['enable', 'disable'])) {
            return new FailedAnswer(__("Invalid action. Please use enable or disable."));
        }
        if ($emails->isEmpty()) {
            return new FailedAnswer(__("Invalid emails. Please use 'me' for yourself, 'all' for all users or a comma-separated list of email addresses."));
        }

        $enable = $action === 'enable';
        $status = $enable ? 'enabled' : 'disabled';
        $msg = $emails->map(function (string $email) use ($user, $action, $enable, $status) {

            if ($email === 'me') {
                $user->gets_audit_report = $enable;
                $user->save();
                return "Report {$status} for yourself";
            }
            if ($email === 'all') {
                User::where('tenant_id', $user->tenant_id)->update(['gets_audit_report' => $enable]);
                return "Report {$status} for all users";
            }

            $u = User::where('email', $email)
                ->where('tenant_id', $user->tenant_id)
                ->first();

            if ($u) {
                $u->gets_audit_report = $enable;
                $u->save();
                return "Report {$status} for {$email}";
            }
            return "{$email} not found";
        })->join("</li><li>");
        return new SuccessfulAnswer("<p>Update completed for:</p><ul><li>{$msg}</li></ul>", [], true);
    }
}
