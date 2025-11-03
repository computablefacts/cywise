<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Smtp\SmtpApiMailer;

class MailStorm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailstorm {apikey} {from} {subject} {body} {emails} {blacklist?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails in batches using SMTP API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apikey = Str::trim((string)$this->argument('apikey'));
        $from = Str::trim((string)$this->argument('from'));
        $subject = Str::trim((string)$this->argument('subject'));
        $body = Str::trim((string)$this->argument('body'));
        $emails = Str::trim((string)$this->argument('emails'));
        $blacklist = Str::trim((string)$this->argument('blacklist'));

        if (empty($apikey)) {
            throw new \Exception("An API key is required.");
        }
        if (!file_exists($subject)) {
            throw new \Exception("Email subject not found.");
        }
        if (!Str::endsWith($subject, '.txt')) {
            throw new \Exception("Email subject must be a TXT file.");
        }
        if (!file_exists($body)) {
            throw new \Exception("Email body not found.");
        }
        if (!Str::endsWith($body, '.html')) {
            throw new \Exception("Email body must be an HTML file.");
        }
        if (!file_exists($emails)) {
            throw new \Exception("Emails not found.");
        }
        if (!Str::endsWith($emails, '.tsv')) {
            throw new \Exception("Emails must be a TSV file.");
        }
        if (file_exists($blacklist)) {
            $blacklist = collect(explode("\n", file_get_contents($blacklist)))
                ->map(fn(string $email) => Str::lower(Str::trim($email)))
                ->filter(fn(string $email) => !empty($email))
                ->toArray();
        } else {
            $blacklist = [];
        }

        $smtp = new SmtpApiMailer($apikey);
        $subject = Str::trim(file_get_contents($subject));
        $body = Str::trim(file_get_contents($body));

        collect(explode("\n", file_get_contents($emails)))
            ->map(function (string $line) {
                $parts = explode("\t", Str::trim($line));
                return [
                    'email' => Str::lower(Str::trim($parts[0] ?? '')),
                    'sending_date' => isset($parts[1]) ? Carbon::createFromFormat('Y-m-d', $parts[1]) : null
                ];
            })
            ->filter(fn(array $obj) => !empty($obj['email']) && $obj['sending_date'] instanceof Carbon && $obj['sending_date']->isSameDay(Carbon::now()))
            ->map(fn(array $obj) => $obj['email'])
            ->filter(fn(string $email) => !in_array($email, $blacklist))
            ->unique()
            ->each(function (string $email) use ($smtp, $from, $subject, $body) {

                $this->info("Sending email to {$email}...");

                $smtp->setFrom($from);
                $smtp->setTo($email);
                $smtp->setTimeout(30);
                $smtp->setSubject($subject);
                $smtp->setHtml($body);

                try {
                    $response = $smtp->sendMail();
                    $result = json_decode($response['body'], true);
                    $nbEmails = 1;
                    if (isset($result['success']) && isset($result['message'])) {
                        $successes = $result['success'];
                        $errors = $nbEmails - $successes;
                    } else {
                        $successes = 0;
                        $errors = $nbEmails;
                    }
                    if ($errors === 0) {
                        $this->info("{$successes} email(s) sent with success.");
                    } else if ($successes === 0) {
                        $this->error("{$errors} email(s) sent with error.");
                    } else {
                        $this->warn("{$successes} email(s) sent with success. {$errors} email(s) sent with error.");
                    }
                    if ($response['code'] !== 200) {
                        $this->error(json_encode($response));
                    }
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                    $this->error($e->getMessage());
                }
            });
    }
}
