<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\Http\Procedures\UsersProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
                    Toggle the gets_audit_report flag for one or more users.
                    The action (such as enable or disable) must come first, followed by a colon and then a comma-separated list of email addresses.
                    For example:
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
                            "description" => "The action to perform followed by a comma-separated list of email addresses, using the format: 'action:email1,email2,email3'.",
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

        $getsAuditReport = $action === 'enable';
        $msg = $emails->flatMap(function (string $email) use ($user, $getsAuditReport) {

            /** @var Builder $query */
            $query = User::query()->where('tenant_id', $user->tenant_id);

            if ($email !== 'all') {
                if ($email === 'me') {
                    $query = $query->where('id', $user->id);
                } else {
                    $query = $query->where('email', $email);
                }
            }
            return $query->get()->map(function (User $u) use ($user, $getsAuditReport) {
                $request = new JsonRpcRequest([
                    'user_id' => $u->id,
                    'gets_audit_report' => $getsAuditReport,
                ]);
                $request->setUserResolver(fn() => $user);
                (new UsersProcedure())->toggleGetsAuditReport($request);
                return __("Report :status for :email", ['status' => $getsAuditReport ? 'enabled' : 'disabled', 'email' => $u->email]);
            });
        })->join("</li><li>");
        return new SuccessfulAnswer("<p>Update completed for:</p><ul><li>{$msg}</li></ul>", [], true);
    }
}
