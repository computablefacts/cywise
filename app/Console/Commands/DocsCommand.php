<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/** @deprecated Remove as soon as this pull-request is accepted : https://github.com/sajya/server/pull/87 */
class DocsCommand extends \Sajya\Server\Commands\DocsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cywise:docs {route}
                                        {--name=docs.html : Name of the generated documentation}
                                        {--path=/api/ : Path where included documentation files are located}';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Throwable
     */
    public function handle(): int
    {
        $routeName = $this->argument('route');

        $route = Route::getRoutes()->getByName($routeName);

        if ($route === null) {
            $this->warn("Route '$routeName' not found");

            return 1;
        }

        $docs = new Docs($route);

        $html = view('sajya::docs', [
            'title' => config('app.name'),
            'uri' => route($routeName),
            'procedures' => $docs->getAnnotations(),
        ]);

        Storage::disk()->put($this->option('path') . $this->option('name'), $html->render());
        $this->info('Documentation was generated successfully.');

        return Command::SUCCESS;
    }
}
