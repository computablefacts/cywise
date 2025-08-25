<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Vault extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vault {action} {key} {value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt/Decrypt value using a given key.';

    // Keep in sync with Helpers.php
    private static function cywise_hash(string $key, string $value): string
    {
        $initializationVector = cywise_random_string(16);
        return $initializationVector . '_' . openssl_encrypt($value, 'AES-256-CBC', $key, 0, $initializationVector);
    }

    // Keep in sync with Helpers.php
    private static function cywise_unhash(string $key, string $value): string
    {
        $initializationVector = strtok($value, '_');
        $value2 = substr($value, strpos($value, '_') + 1);
        return openssl_decrypt($value2, 'AES-256-CBC', $key, 0, $initializationVector);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $key = $this->argument('key');
        $value = $this->argument('value');

        if ($action == 'encrypt') {
            $this->info(Vault::cywise_hash($key, $value));
        } else if ($action == 'decrypt') {
            $this->info(Vault::cywise_unhash($key, $value));
        } else {
            throw new \Exception("Unknown action : {$action}");
        }
    }
}
