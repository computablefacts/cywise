<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailCoach
{
    private function __construct()
    {
        //
    }

    public static function updateSubscribers(string $email, string $name = '', string $list = '5e845dec-efb6-4a26-ab07-b5f38a415cd3' /* users */): bool
    {
        try {
            $domain = config('mail.mailers.mailcoach.domain');
            $token = config('mail.mailers.mailcoach.token');
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("https://{$domain}/api/email-lists/{$list}/subscribers", [
                'email' => $email,
                'first_name' => $name,
                'last_name' => '',
                'skip_confirmation' => true,
                'tags' => [],
            ]);
            if ($response->successful()) {
                $json = $response->json();
                // Log::debug($json);
                return true;
            }
            Log::error($response->body());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return false;
    }
}
