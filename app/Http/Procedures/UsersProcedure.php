<?php

namespace App\Http\Procedures;

use App\Events\SendAuditReport;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use Illuminate\Support\Str;
use Sajya\Server\Procedure;

class UsersProcedure extends Procedure
{
    public static string $name = 'users';

    #[RpcMethod(
        description: "Toggle the envoy of the weekly email report to a given user.",
        params: [
            "user_id" => "An optional user id. If both the user_id and the email are null, the email of the current user is used.",
            "email" => "An optional user email. If both the user_id and the email are null, the email of the current user is used. (string|nullable|email|max:191|exists:users,email)",
            "gets_audit_report" => "true if the user wants to receive the weekly email report, false otherwise. When null, the current value is toggled. (boolean|nullable)"
        ],
        result: [
            "msg" => "A success message.",
        ],
        ai_examples: [
            "if the request is 'arrête d'envoyer des emails à bob@example.com', the input should be '{\"email\":\"bob@example.com\",\"gets_audit_report\":false}'",
            "if the request is 'arrête de m'envoyer des emails', the input should be '{\"email\":null,\"gets_audit_report\":false}'",
            "if the request is 'réactive l'envoie du rapport pour alice@example.com', the input should be '{\"email\":\"alice@example.com\",\"gets_audit_report\":true}'",
            "if the request is 'désactive l'envoie du rapport hebdomadaire', the input should be '{\"email\":null,\"gets_audit_report\":false}'",
        ],
        ai_result: "@json(\$result['msg'])",
    )]
    public function toggleGetsAuditReport(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'user_id' => 'integer|nullable|prohibits:email|exists:users,id',
            'email' => 'string|nullable|prohibits:user_id|email|max:191|exists:users,email',
            'gets_audit_report' => 'boolean|nullable',
        ]);

        /** @var User $loggedInUser */
        $loggedInUser = $request->user();

        if (isset($params['user_id']) && !isset($params['email'])) {
            $user = User::query()
                ->where('id', $params['user_id'])
                ->when($loggedInUser->tenant_id, fn($query) => $query->where('tenant_id', $loggedInUser->tenant_id))
                ->when($loggedInUser->customer_id, fn($query) => $query->where('customer_id', $loggedInUser->customer_id))
                ->firstOrFail();
        } else if (!isset($params['user_id']) && isset($params['email'])) {
            $user = User::query()
                ->where('email', $params['email'])
                ->when($loggedInUser->tenant_id, fn($query) => $query->where('tenant_id', $loggedInUser->tenant_id))
                ->when($loggedInUser->customer_id, fn($query) => $query->where('customer_id', $loggedInUser->customer_id))
                ->firstOrFail();
        } else {
            $user = $loggedInUser;
        }

        $user->gets_audit_report = $params['gets_audit_report'] ?? !$user->gets_audit_report;
        $user->save();

        $status = $user->gets_audit_report ? 'a weekly' : 'no';

        return [
            "msg" => "The user {$user->name} will get {$status} audit report."
        ];
    }

    #[RpcMethod(
        description: "Immediately send the weekly email report to a given user.",
        params: [
            "user_id" => "An optional user id. If both the user_id and the email are null, the email of the current user is used.",
            "email" => "An optional user email. If both the user_id and the email are null, the email of the current user is used. (string|nullable|email|max:255|exists:users,email)",
        ],
        result: [
            "msg" => "A success message.",
        ],
        ai_examples: [
            "if the request is 'envoie une copie du rapport à alice@example.com', the input should be '{\"email\":\"alice@example.com\"}",
            "if the request is 'renvoie moi le rapport', the input should be '{\"email\":null}",
        ],
        ai_result: "@json(\$result['msg'])",
    )]
    public function sendAuditReport(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'user_id' => 'integer|nullable|prohibits:email|exists:users,id',
            'email' => 'string|nullable|prohibits:user_id|email|max:191|exists:users,email',
        ]);

        /** @var User $loggedInUser */
        $loggedInUser = $request->user();

        if (isset($params['user_id']) && !isset($params['email'])) {
            $user = User::query()
                ->where('id', $params['user_id'])
                ->when($loggedInUser->tenant_id, fn($query) => $query->where('tenant_id', $loggedInUser->tenant_id))
                ->when($loggedInUser->customer_id, fn($query) => $query->where('customer_id', $loggedInUser->customer_id))
                ->firstOrFail();
        } else if (!isset($params['user_id']) && isset($params['email'])) {
            $user = User::query()
                ->where('email', $params['email'])
                ->when($loggedInUser->tenant_id, fn($query) => $query->where('tenant_id', $loggedInUser->tenant_id))
                ->when($loggedInUser->customer_id, fn($query) => $query->where('customer_id', $loggedInUser->customer_id))
                ->firstOrFail();
        } else {
            $user = $loggedInUser;
        }

        SendAuditReport::dispatch($user);

        return [
            "msg" => "The email report has been sent to the user {$user->name}."
        ];
    }

    #[RpcMethod(
        description: "Configure Telegram bot token for the current user and returns the webhook URL to set at Telegram.",
        params: [
            "bot_token" => "The Telegram bot token to save for this user.",
        ],
        result: [
            "webhook" => "The absolute URL to configure as Telegram webhook.",
        ]
    )]
    public function setTelegramConfiguration(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'bot_token' => 'required|string|min:10|max:255',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->telegram_bot_token = $params['bot_token'];

        if (empty($user->telegram_webhook_secret)) {
            $user->telegram_webhook_secret = Str::random(48);
        }

        $user->save();

        $baseUrl = Str::rtrim(config('app.url'), '/');
        $webhook = "{$baseUrl}/api/telegram/webhook/{$user->telegram_webhook_secret}";

        return [
            'webhook' => $webhook,
        ];
    }

    #[RpcMethod(
        description: "Get Telegram's configuration for the current user.",
        params: [],
        result: [
            "bot_token" => "The Telegram bot token of the current user.",
            "webhook" => "The absolute URL to configure as Telegram webhook or an empty string.",
        ]
    )]
    public function getTelegramConfiguration(JsonRpcRequest $request): array
    {
        /** @var User $user */
        $user = $request->user();

        if (empty($user->telegram_bot_token) || empty($user->telegram_webhook_secret)) {
            return [
                'webhook' => '',
            ];
        }

        $baseUrl = Str::rtrim(config('app.url'), '/');
        $webhook = "{$baseUrl}/api/telegram/webhook/{$user->telegram_webhook_secret}";

        return [
            'bot_token' => $user->telegram_bot_token,
            'webhook' => $webhook,
        ];
    }

    #[RpcMethod(
        description: "Configure WhatsApp configuration for the current user and returns the webhook URL.",
        params: [
            "access_token" => "The WhatsApp access token.",
            "phone_number_id" => "The WhatsApp phone number ID.",
        ],
        result: [
            "webhook" => "The absolute URL to configure as WhatsApp webhook.",
            "verify_token" => "The verify token to use when configuring the webhook at Meta.",
        ]
    )]
    public function setWhatsAppConfiguration(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'access_token' => 'required|string|min:10|max:512',
            'phone_number_id' => 'required|string|min:1|max:191',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->whatsapp_access_token = $params['access_token'];
        $user->whatsapp_phone_number_id = $params['phone_number_id'];

        if (empty($user->whatsapp_webhook_secret)) {
            $user->whatsapp_webhook_secret = Str::random(48);
        }

        $user->save();

        $baseUrl = Str::rtrim(config('app.url'), '/');
        $webhook = "{$baseUrl}/api/whatsapp/webhook/{$user->whatsapp_webhook_secret}";

        return [
            'webhook' => $webhook,
            'verify_token' => $user->whatsapp_webhook_secret,
        ];
    }

    #[RpcMethod(
        description: "Get WhatsApp's configuration for the current user.",
        params: [],
        result: [
            "access_token" => "The WhatsApp access token of the current user.",
            "phone_number_id" => "The WhatsApp phone number ID of the current user.",
            "webhook" => "The absolute URL to configure as WhatsApp webhook or an empty string.",
            "verify_token" => "The verify token or an empty string.",
        ]
    )]
    public function getWhatsAppConfiguration(JsonRpcRequest $request): array
    {
        /** @var User $user */
        $user = $request->user();

        if (empty($user->whatsapp_access_token) || empty($user->whatsapp_phone_number_id) || empty($user->whatsapp_webhook_secret)) {
            return [
                'webhook' => '',
            ];
        }

        $baseUrl = Str::rtrim(config('app.url'), '/');
        $webhook = "{$baseUrl}/api/whatsapp/webhook/{$user->whatsapp_webhook_secret}";

        return [
            'access_token' => $user->whatsapp_access_token,
            'phone_number_id' => $user->whatsapp_phone_number_id,
            'webhook' => $webhook,
            'verify_token' => $user->whatsapp_webhook_secret,
        ];
    }
}
