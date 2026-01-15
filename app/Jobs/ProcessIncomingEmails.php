<?php

namespace App\Jobs;

use App\AgentSquad\Providers\PromptsProvider;
use App\Http\Procedures\CyberBuddyProcedure;
use App\Http\Procedures\TheCyberBriefProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Mail\SimpleEmail;
use App\Models\Collection;
use App\Models\Conversation;
use App\Models\File;
use App\Models\TimelineItem;
use App\Models\User;
use App\Rules\IsValidCollectionName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\HTMLToMarkdown\HtmlConverter;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Attachment;

class ProcessIncomingEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const string SENDER_CYBERBUDDY = 'cyberbuddy@cywise.io';
    const string SENDER_MEMEX = 'memex@cywise.io';
    const string URL_PATTERN = "/(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:\/[^\"\'\s]*)?/uix";

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 3 * 180; // 9mn

    /**
     * Based on WordPress' _extract_urls function (https://github.com/WordPress/WordPress/blob/master/wp-includes/functions.php),
     * but using the regular expression by @diegoperini (https://gist.github.com/dperini/729294) – which is close to the perfect
     * URL validation regex (https://mathiasbynens.be/demo/url-regex)
     */
    public static function extractAndSummarizeHyperlinks(string $text): array
    {
        preg_match_all(self::URL_PATTERN, $text, $matches);
        $urls = array_values(array_unique(array_map('html_entity_decode', $matches[0])));
        $tcb = new TheCyberBriefProcedure();
        /** @var User $user */
        $user = Auth::user();
        $prompt = PromptsProvider::provide('default_summarize');
        $result = [];

        foreach ($urls as $url) {

            $url = trim($url);

            if (!empty($url)) {

                /** @var TimelineItem $note */
                $note = TimelineItem::fetchNotes($user->id, null, null, 0, [[
                    ['subject', '=', $url]
                ]])->first();

                if ($note) {
                    $result[] = [
                        'url' => $url,
                        'summary' => $note->attributes()['body'],
                    ];
                } else {
                    try {
                        $request = new JsonRpcRequest([
                            'url_or_text' => $url,
                            'prompt' => $prompt,
                        ]);
                        $request->setUserResolver(fn() => $user);
                        $summary = $tcb->summarize($request)['summary'] ?? '';
                        $result[] = [
                            'url' => $url,
                            'summary' => empty($summary) ? "{$url} could not be accessed or summarized." : $summary,
                        ];
                    } catch (\Exception $exception) {
                        Log::error($exception->getMessage());
                        $result[] = [
                            'url' => $url,
                            'summary' => "{$url} could not be accessed or summarized.",
                        ];
                    }
                    if (count($result) > 0) {
                        $note = $result[count($result) - 1];
                        TimelineItem::createNote($user, $note['summary'], $note['url']);
                    }
                }
            }
        }
        return $result;
    }

    public function __construct()
    {
        //
    }

    public function handle()
    {
        try {

            /** @var \Webklex\PHPIMAP\Client $client */
            $client = Client::account('default');
            $client->connect();

            /** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
            $folders = $client->getFolders();

            /** @var \Webklex\PHPIMAP\Folder $folder */
            foreach ($folders as $folder) {
                if ($folder->name !== 'INBOX') {
                    continue;
                }

                /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
                $messages = $folder->messages()->all()->get();

                /** @var \Webklex\PHPIMAP\Message $message */
                foreach ($messages as $message) {

                    $to = $message->getTo()->all();
                    $from = $message->getFrom()->all();
                    $subject = $message->getSubject()->toString();
                    $isCyberBuddy = collect($to)->map(fn(\Webklex\PHPIMAP\Address $address) => $address->mail)->contains(self::SENDER_CYBERBUDDY);
                    $isMemex = collect($to)->map(fn(\Webklex\PHPIMAP\Address $address) => $address->mail)->contains(self::SENDER_MEMEX);

                    if (!$isCyberBuddy && !$isMemex) {
                        continue;
                    }

                    Log::debug("From: {$from[0]->mail}\nTo: {$to[0]->mail}\nSubject: {$subject}");

                    if (count($from) !== 1) {
                        Log::error('Message from multiple addresses!');
                        continue;
                    }

                    // Search the user who sent the email in the database
                    /** @var \Webklex\PHPIMAP\Address $address */
                    $address = $from[0];

                    // Create shadow profile
                    $user = User::getOrCreate($address->mail);
                    $user->actAs(); // otherwise the tenant will not be properly set

                    // Ensure all prompts are properly loaded
                    /* if (Prompt::count() >= 4) {
                        Log::warning($subject);
                        Log::warning("Some prompts are not ready yet. Skipping email processing for now.");
                        continue;
                    } */

                    // Ensure all collections are properly loaded
                    if (File::where('is_deleted', false)->get()->contains(fn(File $file) => !$file->is_embedded)) {
                        Log::warning("Some collections are not ready yet. Skipping email processing for now.");
                    } else if ($isCyberBuddy) {
                        $this->cyberBuddy($user, $message);
                    } else if ($isMemex) {
                        $this->memex($user, $message);
                    }
                }
            }

            $client->disconnect();

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    private function cyberBuddy(User $user, \Webklex\PHPIMAP\Message $message): void
    {
        if ($message->hasTextBody()) {
            $body = $message->getTextBody();
        } else if ($message->hasHTMLBody()) {
            $body = $message->getHTMLBody();
        } else {
            $body = "";
        }

        // Extract the thread id in order to be able to load the existing conversation
        // If the thread id cannot be found, a new conversation is created
        $threadId = null;
        $matches = [];

        preg_match_all("/\s*thread_id=(?<threadid>[a-zA-Z0-9]{10})\s*/i", $body, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (!empty($match['threadid'])) {
                $threadId = $match['threadid'];
                break;
            }
        }
        if (empty($threadId)) {
            $threadId = Str::random(10);
        }

        /** @var Conversation $conversation */
        $conversation = Conversation::where('thread_id', $threadId)
            ->where('format', Conversation::FORMAT_V1)
            ->where('created_by', $user->id)
            ->first();

        $conversation = $conversation ?? Conversation::create([
            'thread_id' => $threadId,
            'dom' => json_encode([]),
            'autosaved' => true,
            'created_by' => $user->id,
            'format' => Conversation::FORMAT_V1,
        ]);

        if ($message->hasTextBody()) {
            // Remove previous messages i.e. rows starting with >
            $body = trim(preg_replace("/^(>.*)|(On\s+.*\s+wrote:)[\n\r]?$/im", '', $message->getTextBody()));
        } else if ($message->hasHTMLBody()) {
            $body = trim((new HtmlConverter())->convert($message->getHTMLBody()));
        } else {
            $body = "";
        }

        Log::debug("body={$body}");

        // Call CyberBuddy
        $request = new JsonRpcRequest([
            'thread_id' => $threadId,
            'directive' => $body,
        ]);
        $request->setUserResolver(fn() => $user);
        $response = (new CyberBuddyProcedure())->ask($request);
        $subject = $message->getSubject()->toString();
        $body = $response['html'] ?? '';
        $body = Str::before($body, '<br><br><b>Sources :</b>'); // remove sources
        $body = preg_replace("/\[((\d+,?)+)]/", "", $body); // remove references

        SimpleEmail::sendEmail(
            "Re: {$subject}",
            "CyberBuddy vous répond !",
            $body,
            $user->email,
            self::SENDER_CYBERBUDDY
        );

        if (!$message->move('CyberBuddy')) {
            Log::error('Message could not be moved to the CyberBuddy folder!');
        }
    }

    private function memex(User $user, \Webklex\PHPIMAP\Message $message): void
    {
        if ($message->hasTextBody()) {
            // Remove previous messages i.e. rows starting with >
            $body = trim(preg_replace("/^(>.*)|(On\s+.*\s+wrote:)[\n\r]?$/im", '', $message->getTextBody()));
        } else if ($message->hasHTMLBody()) {
            $body = trim((new HtmlConverter())->convert($message->getHTMLBody()));
        } else {
            $body = "";
        }
        if (!empty($body)) {

            TimelineItem::createNote($user, $body, $message->getSubject()->toString());
            self::extractAndSummarizeHyperlinks($body);

            if ($message->hasAttachments()) {

                $collection = $this->getOrCreateCollection("privcol{$user->id}", 0);

                if ($collection) { // TODO : move to privcol{user_id} ?
                    $message->attachments()->each(function (Attachment $attachment) use ($user, $collection) {
                        if (!$attachment->save("/tmp/")) {
                            TimelineItem::createNote($user, "Attachment {$attachment->filename} could not be added to {$collection->name}.", "An error occurred.");
                            Log::error("Attachment {$attachment->name} could not be saved!");
                        } else {
                            $path = "/tmp/{$attachment->filename}";
                            // TODO : deal with duplicate files using the md5/sha1 file hash
                            $url = \App\Http\Controllers\CyberBuddyController::saveLocalFile($collection, $path);
                            unlink($path);
                            TimelineItem::createNote($user, "{$attachment->filename} has been added to {$collection->name}.", "Attachment saved!");
                        }
                    });
                }
            }
        }
        if (!$message->move('Memex')) {
            Log::error('Message could not be moved to the Memex folder!');
        }
    }

    private function getOrCreateCollection(string $collectionName, int $priority): ?Collection
    {
        /** @var \App\Models\Collection $collection */
        $collection = Collection::where('name', $collectionName)
            ->where('is_deleted', false)
            ->first();
        if (!$collection) {
            if (!IsValidCollectionName::test($collectionName)) {
                Log::error("Invalid collection name : {$collectionName}");
                return null;
            }
            $collection = Collection::create([
                'name' => $collectionName,
                'priority' => max($priority, 0),
            ]);
        }
        return $collection;
    }
}
