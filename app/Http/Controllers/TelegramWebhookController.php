<?php

namespace App\Http\Controllers;

use App\Http\Procedures\CyberBuddyProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Conversation;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, string $secret)
    {
        // Identify the owner user by their per-user webhook secret
        /** @var \App\Models\User|null $user */
        $user = User::where('telegram_webhook_secret', $secret)->first();

        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'Forbidden.'], 403);
        }
        if (!$user->telegram_bot_token) {
            return response()->json(['ok' => false, 'error' => "Telegram webhook: bot token not configured."], 500);
        }

        $update = $request->all();

        // Basic support for message updates
        $message = $update['message'] ?? $update['edited_message'] ?? null;

        if (!$message) {
            return response()->json(['ok' => true]); // ignore non-message updates
        }

        $chat = $message['chat'] ?? [];
        $chatId = $chat['id'] ?? null;
        $text = isset($message['text']) ? Str::trim((string)$message['text']) : '';

        if ($chatId === null || $text === '') {
            return response()->json(['ok' => true]);
        }

        // $user is resolved by secret above
        $user->actAs();

        // Map chat id to a valid 10-char thread id used by our Conversations
        $threadId = $this->threadIdFromChatId($chatId);

        /** @var Conversation|null $conversation */
        $conversation = Conversation::where('thread_id', $threadId)
            ->where('format', Conversation::FORMAT_V1)
            ->where('created_by', $user->id)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'thread_id' => $threadId,
                'created_by' => $user->id,
                'format' => Conversation::FORMAT_V1,
                'dom' => json_encode([]),
                'autosaved' => false,
                'description' => null,
            ]);
        }

        $req = new JsonRpcRequest([
            'thread_id' => $threadId,
            'directive' => $text,
        ]);
        $req->setUserResolver(fn() => $user);
        $response = (new CyberBuddyProcedure())->ask($req);
        $answer = $response['html'] ?? '';
        $answer = Str::before($answer, '<br><br><b>Sources :</b>'); // remove sources
        $answer = preg_replace("/\[((\d+,?)+)]/", "", $answer); // remove references

        // Telegram's HTML parse_mode only supports a limited set of tags.
        // Unsupported tags like <p>, <div>, etc. cause a 400 Bad Request.
        // We replace <p> and <br> with newlines, and strip everything else except <b>, <i>, <a>, <code>, <pre>.
        // See https://core.telegram.org/bots/api#formatting-options for details.
        $answer = str_replace(['<p>', '</p>'], ["\n", "\n"], $answer);
        $answer = str_replace(['<br>', '<br/>', '<br />'], "\n", $answer);
        $answer = strip_tags($answer, '<b><i><a><code><pre>');
        $answer = Str::trim($answer);

        if ($answer === '') {
            $answer = 'Je n\'ai pas pu formater la réponse. Pouvez-vous reformuler votre demande ?';
        }

        // Reply to Telegram using HTML formatting
        try {
            $client = new Client(['base_uri' => 'https://api.telegram.org']);
            $client->post("/bot{$user->telegram_bot_token}/sendMessage", [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $answer,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                ],
                'timeout' => 10,
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage failed: ' . $e->getMessage());
        }
        return response()->json(['ok' => true]);
    }

    /**
     * Deterministically map a Telegram chat ID (can be negative) to a 10-char base36 string.
     * This satisfies the thread id validation: [a-zA-Z0-9]{10}
     */
    private function threadIdFromChatId(int|string $chatId): string
    {
        // Normalize to integer and to a positive value, then base36 encode
        $n = (int)$chatId;
        if ($n < 0) {
            $n = -$n;
        }
        $base36 = strtoupper(base_convert((string)max($n, 0), 10, 36));
        // Ensure at least 10 chars by left-padding with random-looking but deterministic filler based on checksum
        $hash = substr(strtoupper(hash('crc32b', (string)$chatId)), 0, 10);
        $padded = substr($hash . $base36, -10);
        // Guarantee it is exactly 10 alphanum
        return str_pad($padded, 10, '0', STR_PAD_LEFT);
    }
}
