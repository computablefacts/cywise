<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Packer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packer {action} {dir} {pattern}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compresses ("pack") or extracts ("unpack") all files whose names follow a specific pattern within an archive.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $dir = $this->argument('dir');
        $pattern = $this->argument('pattern');

        if ($action === 'pack') {
            $file = cywise_pack_files($dir, $pattern);
            $this->info("Archive created successfully: {$file}");
        } else if ($action === 'unpack') {
            $files = cywise_unpack_files($dir, $pattern);
            $this->info("Files extracted successfully:\n" . implode("\n", $files));
        } else {
            throw new \Exception("Unknown action : {$action}");
        }
    }
}
