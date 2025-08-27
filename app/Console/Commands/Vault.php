<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

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
    protected $description = 'Encrypt/Decrypt a given value (string or file) using a given key.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $key = $this->argument('key');
        $value = $this->argument('value');
        $isString = !file_exists($value);

        if ($action == 'encrypt') {
            if ($isString) {
                $this->info(cywise_hash_ext($key, $value));
            } else {
                $file = "$value.enc";
                $output = cywise_encrypt_file($key, $value);
                if (file_exists($file)) {
                    unlink($file);
                }
                copy($output, $file);
            }
        } else if ($action == 'decrypt') {
            if ($isString) {
                $this->info(cywise_unhash_ext($key, $value));
            } else {
                $file = Str::replace('.enc', '.dec', $value);
                $output = cywise_decrypt_file($key, $value);
                if (file_exists($file)) {
                    unlink($file);
                }
                copy($output, $file);
            }
        } else {
            throw new \Exception("Unknown action : {$action}");
        }
    }
}
