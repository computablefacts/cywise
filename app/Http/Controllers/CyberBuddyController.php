<?php

namespace App\Http\Controllers;

use App\Events\IngestFile;
use App\Helpers\ApiUtilsFacade as ApiUtils;
use App\Models\Chunk;
use App\Models\Conversation;
use App\Models\File;
use App\Models\Template;
use App\Models\User;
use App\Models\YnhFramework;
use App\Rules\IsValidCollectionName;
use App\Rules\IsValidFileType;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/** @deprecated */
class CyberBuddyController extends Controller
{
    public function __construct()
    {
        //
    }

    public static function enhanceAnswerWithSources(string $answer, Collection $sources): string
    {
        $matches = [];
        // Extract: [12] from [[12]] or [[12] and [13]] from [[12],[13]]
        $isOk = preg_match_all("/\[\[\d+]]|\[\[\d+]|\[\d+]]/", $answer, $matches);
        if (!$isOk) {
            return Str::replace(["\n\n", "\n-"], "<br>", $answer);
        }
        $references = [];
        /** @var array $refs */
        $refs = $matches[0];
        foreach ($refs as $ref) {
            $id = Str::replace(['[', ']'], '', $ref);
            /** @var array $tooltip */
            $tooltip = $sources->filter(fn($ctx) => $ctx['id'] == $id)->first();
            /** @var Chunk $chunk */
            $chunk = Chunk::find($id);
            /** @var File $file */
            $file = $chunk?->file()->first();
            $src = $file ? "<a href=\"{$file->downloadUrl()}\" style=\"text-decoration:none;color:black\">{$file->name_normalized}.{$file->extension}</a>, p. {$chunk->page}" : "";
            if ($tooltip) {
                if (Str::startsWith($tooltip['text'], 'ESSENTIAL DIRECTIVE')) {
                    $color = '#1DD288';
                } else if (Str::startsWith($tooltip['text'], 'STANDARD DIRECTIVE')) {
                    $color = '#C5C3C3';
                } else if (Str::startsWith($tooltip['text'], 'ADVANCED DIRECTIVE')) {
                    $color = '#FDC99D';
                } else {
                    $color = '#F8B500';
                }
                $answer = Str::replace($ref, "<b style=\"color:{$color}\">[{$id}]</b>", $answer);
                $references[$id] = "
                  <li style=\"padding:0;margin-bottom:0.25rem\">
                    <b style=\"color:{$color}\">[{$id}]</b>&nbsp;
                    <div class=\"cb-tooltip-list\">
                      {$src}
                      <span class=\"cb-tooltiptext cb-tooltip-list-top\" style=\"background-color:{$color};color:#444;\">
                        {$tooltip['text']}
                      </span>
                    </div>
                  </li>
                ";
            }
        }
        ksort($references);
        $answer = "{$answer}<br><br><b>Sources :</b><ul style=\"padding:0\">" . collect($references)->values()->join("") . "</ul>";
        return Str::replace(["\n\n", "\n-"], "<br>", $answer);
    }

    public static function removeSourcesFromAnswer(string $answer): string
    {
        // Remove sources such as [[12]] or [[12],[13]] from the answer
        return preg_replace("/\[((\[\d+],?)+)]/", "", $answer);
    }

    public static function saveDistantFile(\App\Models\Collection $collection, string $url, bool $triggerIngest = true, bool $test = false): ?string
    {
        $content = file_get_contents($url);

        if ($content === false) {
            return null;
        }

        $filename = pathinfo(Str::afterLast($url, '/'), PATHINFO_FILENAME);
        $extension = pathinfo(Str::afterLast($url, '/'), PATHINFO_EXTENSION);
        $name_normalized = strtolower(trim($filename));
        $path = "/tmp/{$name_normalized}.{$extension}";
        file_put_contents($path, $content);

        return self::saveLocalFile($collection, $path, $triggerIngest, $test);
    }

    public static function saveLocalFile(\App\Models\Collection $collection, string $path, bool $triggerIngest = true, bool $test = false): ?string
    {
        $name = \Illuminate\Support\Facades\File::name($path);
        $extension = \Illuminate\Support\Facades\File::extension($path);
        $originalName = "{$name}.{$extension}";
        $mimeType = \Illuminate\Support\Facades\File::mimeType($path);
        $error = null;
        $uploadedFile = new UploadedFile($path, $originalName, $mimeType, $error, $test);
        return self::saveUploadedFile($collection, $uploadedFile, $triggerIngest);
    }

    public static function saveUploadedFile(\App\Models\Collection $collection, UploadedFile $file, bool $triggerIngest = true): ?string
    {
        // Extract file metadata
        $file_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $file_extension = $file->getClientOriginalExtension();
        $file_path = $file->getClientOriginalPath();
        $file_size = $file->getSize();
        $mime_type = $file->getClientMimeType();
        $file_md5 = md5_file($file->getRealPath());
        $file_sha1 = sha1_file($file->getRealPath());

        if ($file_extension === 'gz') {

            $in = $file->getRealPath();
            $handleIn = gzopen($in, 'rb');

            if ($handleIn) {

                $out = "/tmp/{$file_name}";
                $handleOut = fopen($out, 'wb');

                if (!$handleOut) {
                    gzclose($handleIn);
                } else {
                    while (!gzeof($handleIn)) {
                        fwrite($handleOut, gzread($handleIn, 4096));
                    }
                    gzclose($handleIn);
                    fclose($handleOut);
                    return self::saveLocalFile($collection, $out, $triggerIngest);
                }
            }
        }

        /** @var ?File $file_exists */
        $file_exists = $collection->files()->where('md5', $file_md5)->where('sha1', $file_sha1)->first();

        if ($file_exists) { // ensure each file is added only once to a given collection
            return $file_exists->downloadUrl();
        }
        if ($file_extension === 'jsonl' && $mime_type === 'application/octet-stream') {
            $mime_type = 'application/x-ndjason';
        }

        // Normalize filename
        $file_name_normalized = strtolower(trim($file_name));
        $file_name_normalized = preg_replace('/[\s\-]+/', '-', $file_name_normalized);
        $file_name_normalized = preg_replace('/[^-a-z0-9._]+/', '', $file_name_normalized);

        /** @var File $fileRef */
        $fileRef = $collection->files()->create([
            'name' => $file_name,
            'name_normalized' => $file_name_normalized,
            'extension' => $file_extension,
            'path' => $file_path,
            'size' => $file_size,
            'md5' => $file_md5,
            'sha1' => $file_sha1,
            'mime_type' => $mime_type,
            'secret' => Str::random(32),
            'created_by' => Auth::user()->id,
        ]);

        // Copy file to S3
        $storage = Storage::disk('files-s3');
        $filepath = self::storageFilePath($collection);
        $filename = self::storageFileName($fileRef);

        if (!$storage->exists($filepath)) {
            if (!$storage->makeDirectory($filepath)) {
                $fileRef->delete();
                return null;
            }
        }
        if (!$storage->putFileAs($filepath, $file, $filename)) {
            $fileRef->delete();
            return null;
        }

        // Process file ex. create embeddings
        if ($triggerIngest) {
            IngestFile::dispatch(Auth::user(), $collection->name, $fileRef->id);
        }
        return $fileRef->downloadUrl();
    }

    private static function storageFilePath(\App\Models\Collection $collection): string
    {
        return "/cyber-buddy/{$collection->id}";
    }

    private static function storageFileName(File $file): string
    {
        return "{$file->id}_{$file->name_normalized}.{$file->extension}";
    }

    public function templates()
    {
        return Template::where('readonly', true)
            ->orderBy('name', 'asc')
            ->get()
            ->concat(
                Template::where('readonly', false)
                    ->where('created_by', Auth::user()->id)
                    ->orderBy('name', 'asc')
                    ->get()
            )
            ->map(function (Template $template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'template' => $template->template,
                    'type' => $template->readonly ? 'template' : 'draft',
                    'user' => User::find($template->created_by)->name,
                ];
            });
    }

    public function saveTemplate(Request $request)
    {
        // TODO : validate request
        $id = $request->integer('id', 0);
        $name = $request->string('name', '');
        $blocks = $request->input('template', []);
        $model = $request->boolean('is_model', false);

        if (isset($blocks) && count($blocks) > 0) {
            if ($id === 0) {
                $template = Template::create([
                    'name' => Str::replace('v', '', $name),
                    'template' => $blocks,
                    'readonly' => $model,
                ]);
            } else {
                $template = Template::where('id', $id)->where('readonly', false)->first();
                $version = ($template && Str::contains($template->name, 'v') ? (int)Str::afterLast($template->name, 'v') : 0) + 1;
                if ($template) {
                    $template->name = Str::beforeLast($template->name, 'v') . "v{$version}";
                    $template->template = $blocks;
                    $template->save();
                } else {
                    $userId = Auth::user()->id;
                    $template = Template::create([
                        'name' => "{$name} u{$userId}v1",
                        'template' => $blocks,
                        'readonly' => false,
                    ]);
                }
            }
            return [
                'id' => $template->id,
                'name' => $template->name,
                'template' => $template->template,
                'type' => $template->readonly ? 'template' : 'draft',
                'user' => User::find($template->created_by)->name,
            ];
        }
        return [];
    }

    public function deleteTemplate(int $id)
    {
        Template::where('id', $id)->where('readonly', false)->delete();
        return response()->json([
            'success' => __('The template has been deleted!'),
        ]);
    }

    public function llm1(Request $request)
    {
        // TODO : validate request
        $collection = $request->string('collection');
        $prompt = $request->string('prompt');
        $response = ApiUtils::ask_chunks($prompt, $collection, null, true, true, 'fr', 10, true);
        if (!isset($response['error']) || $response['error']) {
            return 'Une erreur s\'est produite. Veuillez réessayer ultérieurement.';
        }
        return self::removeSourcesFromAnswer($response['response']);
    }

    public function llm2(Request $request)
    {
        // TODO : validate request
        $template = $request->string('template');
        $prompt = $request->string('prompt');
        $questionsAndAnswers = $request->input('q_and_a', []);
        $response = ApiUtils::generate_from_template($template, $prompt, $questionsAndAnswers);
        if (!isset($response['error']) || $response['error']) {
            return 'Une erreur s\'est produite. Veuillez réessayer ultérieurement.';
        }
        return self::removeSourcesFromAnswer($response['response']);
    }

    public function streamFile(string $secret, Request $request)
    {
        /** @var File $file */
        $file = File::where('secret', $secret)->where('is_deleted', false)->first();

        if (!$file) {
            return response()->json(['error' => 'Unknown file.'], 500);
        }

        /** @var \App\Models\Collection $collection */
        $collection = $file->collection()->where('is_deleted', false)->first();

        if (!$collection) {
            return response()->json(['error' => 'Unknown collection.'], 500);
        }

        $storage = Storage::disk('files-s3');
        $path = $this->storagePath($collection, $file);

        if (!$storage->exists($path)) {
            return response()->json(['error' => 'Unknown storage path.'], 500);
        }
        return $storage->response($path, null, [
            'pragma' => 'private',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function downloadFile(string $secret, Request $request)
    {
        $params = $request->validate([
            'page' => 'integer|min:1',
        ]);

        /** @var File $file */
        $file = File::where('secret', $secret)->where('is_deleted', false)->first();

        if (!$file) {
            return response()->json(['error' => 'Unknown file.'], 500);
        }

        /** @var \App\Models\Collection $collection */
        $collection = $file->collection()->where('is_deleted', false)->first();

        if (!$collection) {
            return response()->json(['error' => 'Unknown collection.'], 500);
        }

        $storage = Storage::disk('files-s3');
        $path = $this->storagePath($collection, $file);

        if (!$storage->exists($path)) {
            return response()->json(['error' => 'Unknown storage path.'], 500);
        }

        $page = $params['page'] ?? -1;

        if ($file->isPdf() && $page > 0) {

            $rawFile = self::storageFileName($file);

            if (!file_exists("/tmp/{$rawFile}")) {
                file_put_contents("/tmp/{$rawFile}", $storage->get($path));
            }
            if (file_exists("/tmp/{$rawFile}")) {

                $extractedPage = $this->tmpFileName($file, $page);

                if (!file_exists("/tmp/{$extractedPage}")) {

                    $process = Process::fromShellCommandline("pdfseparate -f \"{$page}\" -l \"$page\" \"/tmp/{$rawFile}\" \"/tmp/{$extractedPage}\"");
                    $process->run();

                    if (!$process->isSuccessful()) {
                        return $storage->download($path, null, [
                            'pragma' => 'private',
                            'Cache-Control' => 'private, max-age=3600',
                        ]);
                    }
                }
                if (file_exists("/tmp/{$extractedPage}")) {
                    return response()->download("/tmp/{$extractedPage}", null, [
                        'pragma' => 'private',
                        'Cache-Control' => 'private, max-age=3600',
                    ]);
                }
            }
        }

        $rawFile = self::storageFileName($file);

        if (file_exists("/tmp/{$rawFile}")) { // bypass S3 if possible
            return response()->download("/tmp/{$rawFile}", null, [
                'pragma' => 'private',
                'Cache-Control' => 'private, max-age=3600',
            ]);
        }
        return $storage->download($path, null, [
            'pragma' => 'private',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function unloadFramework(int $id, Request $request)
    {
        /** @var YnhFramework $framework */
        $framework = YnhFramework::where('id', $id)->firstOrFail();
        if ($framework->collection()) {
            File::where('is_deleted', false)
                ->where('collection_id', $framework->collection()->id)
                ->where('name', trim(basename($framework->file, '.jsonl')))
                ->where('extension', 'jsonl')
                ->delete();
        }
        return response()->json([
            'success' => 'The framework has been unloaded and will be removed soon.',
        ]);
    }

    public function loadFramework(int $id, Request $request)
    {
        /** @var YnhFramework $framework */
        $framework = YnhFramework::where('id', $id)->firstOrFail();

        /** @var \App\Models\Collection $collection */
        $collection = \App\Models\Collection::where('name', $framework->collectionName())
            ->where('is_deleted', false)
            ->first();

        if (!$collection) {
            if (!IsValidCollectionName::test($framework->collectionName())) {
                return response()->json(['error' => 'Invalid collection name.'], 500);
            }
            $collection = \App\Models\Collection::create(['name' => $framework->collectionName()]);
        }

        $path = Str::replace('.jsonl.gz', '.2.jsonl.gz', $framework->path());
        $url = self::saveLocalFile($collection, $path);

        if ($url) {
            return response()->json([
                'success' => 'The framework has been loaded and will be processed soon.',
                'url' => $url,
            ]);
        }
        return response()->json(['error' => 'The framework could not be loaded.'], 500);
    }

    public function uploadOneFile(Request $request)
    {
        if (!Auth::user()->canUseCyberBuddy()) {
            response()->json([
                'error' => 'Missing permission.',
                'urls_success' => [],
                'filenames_error' => [],
            ], 500);
        }

        $params = $request->validate([
            'collection' => 'required|string',
            'file' => [
                'required',
                'file',
                'max:10240',
                new IsValidFileType()
            ],
        ]);

        $collectionName = $params['collection'];
        $file = $params['file'];

        /** @var \App\Models\Collection $collection */
        $collection = \App\Models\Collection::where('name', $collectionName)->where('is_deleted', false)->first();

        if (!$collection) {
            if (!IsValidCollectionName::test($collectionName)) {
                return response()->json(['error' => 'Invalid collection name.'], 500);
            }
            $collection = \App\Models\Collection::create(['name' => $collectionName]);
        }
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'Missing file content.'], 500);
        }

        $url = self::saveUploadedFile($collection, $file);

        if ($url) {
            return response()->json([
                'success' => 'The file has been saved and will be processed soon.',
                'url' => $url,
            ]);
        }
        return response()->json(['error' => 'The file could not be saved.'], 500);
    }

    public function uploadManyFiles(Request $request)
    {
        if (!Auth::user()->canUseCyberBuddy()) {
            response()->json([
                'error' => 'Missing permission.',
                'urls_success' => [],
                'filenames_error' => [],
            ], 500);
        }

        $params = $request->validate([
            'collection' => 'required|string',
            'files.*' => [
                'required',
                'file',
                'max:10240',
                new IsValidFileType()
            ],
        ]);

        $collectionName = $params['collection'];
        $files = $params['files'];

        /** @var \App\Models\Collection $collection */
        $collection = \App\Models\Collection::where('name', $collectionName)->where('is_deleted', false)->first();

        if (!$collection) {
            if (!IsValidCollectionName::test($collectionName)) {
                return response()->json(['error' => 'Invalid collection name.'], 500);
            }
            $collection = \App\Models\Collection::create(['name' => $collectionName]);
        }

        $successes = [];
        $errors = [];

        foreach ($files as $file) {
            $url = self::saveUploadedFile($collection, $file);
            if ($url) {
                $successes[] = $url;
            } else {
                $errors[] = $file->getClientOriginalName();
            }
        }
        if (count($errors) <= 0) {
            return response()->json([
                'success' => 'All files have been saved and will be processed soon.',
                'urls' => $successes,
            ]);
        }
        return response()->json([
            'error' => 'One or more files could not be saved.',
            'urls_success' => $successes,
            'filenames_error' => $errors,
        ], 500);
    }

    public function deleteConversation(int $id)
    {
        Conversation::where('id', $id)->delete();
        return response()->json([
            'success' => __('The conversation has been deleted!'),
        ]);
    }

    private function storagePath(\App\Models\Collection $collection, File $file): string
    {
        return self::storageFilePath($collection) . "/" . self::storageFileName($file);
    }

    private function tmpFileName(File $file, int $page): string
    {
        return "{$file->id}_{$page}_{$file->name_normalized}.{$file->extension}";
    }
}
