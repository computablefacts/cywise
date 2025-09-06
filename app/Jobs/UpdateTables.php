<?php

namespace App\Jobs;

use App\Events\ImportTable;
use App\Helpers\ClickhouseUtils;
use App\Helpers\TableStorage;
use App\Models\Table;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class UpdateTables implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 3 * 180; // 9mn

    public function __construct()
    {
        //
    }

    public function handle()
    {
        Table::where('updatable', true)
            ->get()
            ->each(function (Table $table) {

                /** @var User $user */
                $user = User::findOrFail($table->created_by);
                $user->actAs();

                $disk = TableStorage::inDisk($table->credentials);

                collect($disk->files())->filter(function ($file) use ($table) {
                    return ClickhouseUtils::normalizeTableName($file) === $table->name;
                })->filter(function ($file) use ($disk, $table) {
                    return (!$table->finished_at || Carbon::createFromTimestamp($disk->lastModified($file))->isAfter($table->finished_at));
                })->each(function ($file) use ($user, $table) {

                    $credentials = $table->credentials;
                    $copy = $table->copied;
                    $deduplicate = $table->deduplicated;
                    $updatable = $table->updatable;
                    $description = $table->description;
                    $columns = $table->schema;

                    ImportTable::dispatch($user, $credentials, $copy, $deduplicate, $updatable, $file, $columns, $description);
                });
            });

        // TODO : update dependencies
    }
}
