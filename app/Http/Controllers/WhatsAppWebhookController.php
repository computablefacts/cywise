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

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request, string $secret)
    {
        /** @var \App\Models\User|null $user */
        $user = User::where('whatsapp_webhook_secret', $secret)->first();

        if (!$user) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        // WhatsApp Webhook Verification (Hub challenge)
        if ($request->isMethod('GET')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if ($mode === 'subscribe' && $token === $secret) {
                return response($challenge, 200);
            }
            return response('Forbidden', 403);
        }

        if (!$user->whatsapp_access_token || !$user->whatsapp_phone_number_id) {
            Log::error("WhatsApp webhook: configuration missing for user {$user->id}");
            return response()->json(['ok' => true]);
        }

        $data = $request->all();

        // Basic support for messages
        // WhatsApp API payload structure: entry[0].changes[0].value.messages[0]
        $entry = $data['entry'][0] ?? null;
        $change = $entry['changes'][0] ?? null;
        $value = $change['value'] ?? null;
        $message = $value['messages'][0] ?? null;

        if (!$message || ($message['type'] ?? '') !== 'text') {
            return response()->json(['ok' => true]);
        }

        $from = $message['from'] ?? null; // Sender's phone number
        $text = $message['text']['body'] ?? '';
        $text = Str::trim((string)$text);

        if (!$from || $text === '') {
            return response()->json(['ok' => true]);
        }

        $user->actAs();

        // Map phone number to a valid 10-char thread id
        $threadId = $this->threadIdFromPhoneNumber($from);

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
                'description' => "Conversation via WhatsApp ({$from})",
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

        // WhatsApp formatting (Markdown-like)
        $answer = str_replace(['<p>', '</p>'], ["", "\n\n"], $answer);
        $answer = str_replace(['<br>', '<br/>', '<br />'], "\n", $answer);
        $answer = str_replace(['<b>', '</b>'], '*', $answer);
        $answer = str_replace(['<i>', '</i>'], '_', $answer);
        $answer = str_replace(['<code>', '</code>'], '`', $answer);
        $answer = str_replace(['<pre>', '</pre>'], '```', $answer);
        $answer = strip_tags($answer);
        $answer = Str::trim($answer);

        if ($answer === '') {
            $answer = 'Je n\'ai pas pu formater la réponse. Pouvez-vous reformuler votre demande ?';
        }

        // Reply to WhatsApp
        try {
            $client = new Client(['base_uri' => 'https://graph.facebook.com/v17.0/']);
            $client->post("{$user->whatsapp_phone_number_id}/messages", [
                'headers' => [
                    'Authorization' => "Bearer {$user->whatsapp_access_token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to' => $from,
                    'type' => 'text',
                    'text' => [
                        'body' => $answer,
                    ],
                ],
                'timeout' => 10,
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp sendMessage failed: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    private function threadIdFromPhoneNumber(string $phoneNumber): string
    {
        // Normalize: remove all non-digits
        $digits = preg_replace('/\D/', '', $phoneNumber);
        $n = (int)$digits;
        $base36 = strtoupper(base_convert((string)max($n, 0), 10, 36));
        $hash = substr(strtoupper(hash('crc32b', $phoneNumber)), 0, 10);
        $padded = substr($hash . $base36, -10);
        return str_pad($padded, 10, '0', STR_PAD_LEFT);
    }
}
