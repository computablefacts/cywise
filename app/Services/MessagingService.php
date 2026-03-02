<?php

namespace App\Services;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MessagingService
{
    public function sendTelegram(User $user, string $message, ?string $chatId = null): bool
    {
        $chatId = $chatId ?: $user->telegram_chat_id;

        if (!$user->telegram_bot_token || !$chatId) {
            return false;
        }
        try {
            $client = new Client(['base_uri' => 'https://api.telegram.org']);
            $client->post("/bot{$user->telegram_bot_token}/sendMessage", [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                ],
                'timeout' => 10,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendWhatsApp(User $user, string $message, ?string $to = null): bool
    {
        $to = $to ?: $user->whatsapp_phone_number;

        if (!$user->whatsapp_access_token || !$user->whatsapp_phone_number_id || !$to) {
            return false;
        }
        try {
            $client = new Client(['base_uri' => 'https://graph.facebook.com/v25.0/']);
            $client->post("{$user->whatsapp_phone_number_id}/messages", [
                'headers' => [
                    'Authorization' => "Bearer {$user->whatsapp_access_token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ],
                'timeout' => 10,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('WhatsApp sendMessage failed: ' . $e->getMessage());
            return false;
        }
    }

    public function formatForTelegram(string $html): string
    {
        $answer = str_replace(['<p>', '</p>'], ["", "\n\n"], $html);
        $answer = str_replace(['<br>', '<br/>', '<br />'], "\n", $answer);
        $answer = strip_tags($answer, '<b><i><a><code><pre>');
        $answer = preg_replace("/\n\n+/", "\n\n", $answer);
        return Str::trim($answer);
    }

    public function formatForWhatsApp(string $html): string
    {
        $answer = str_replace(['<p>', '</p>'], ["", "\n\n"], $html);
        $answer = str_replace(['<br>', '<br/>', '<br />'], "\n", $answer);
        $answer = str_replace(['<b>', '</b>'], '*', $answer);
        $answer = str_replace(['<i>', '</i>'], '_', $answer);
        $answer = str_replace(['<code>', '</code>'], '`', $answer);
        $answer = str_replace(['<pre>', '</pre>'], '```', $answer);
        $answer = strip_tags($answer);
        $answer = preg_replace("/\n\n+/", "\n\n", $answer);
        return Str::trim($answer);
    }
}
