<?php

use App\Events\BeginVulnsScan;
use App\Events\EndPortsScan;
use App\Events\EndVulnsScan;
use App\Http\Controllers\TablesUploadController;
use App\Models\Asset;
use App\Models\Honeypot;
use App\Models\Port;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Sajya\Server\Middleware\GzipCompress;
use Wave\Facades\Wave;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return auth()->user();
});

Wave::api();

// Posts Example API Route
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/posts', '\App\Http\Controllers\Api\ApiController@posts');
});

Route::group([
    'prefix' => 'public',
], function () {
    Route::post('honeypots/{dns}', function (string $dns, \Illuminate\Http\Request $request) {

        if (!$request->hasFile('data')) {
            return response('Missing attachment', 500)
                ->header('Content-Type', 'text/plain');
        }

        $file = $request->file('data');

        if (!$file->isValid()) {
            return response('Invalid attachment', 500)
                ->header('Content-Type', 'text/plain');
        }

        $honeypot = Honeypot::where('dns', $dns)->first();

        if (!$honeypot) {
            return response('Unknown honeypot', 500)
                ->header('Content-Type', 'text/plain');
        }

        $filename = $file->getClientOriginalName();
        $timestamp = Carbon::createFromFormat('Y.m.d_H.i.s', \Illuminate\Support\Str::substr($filename, \Illuminate\Support\Str::position($filename, '-access.') + 8, 19));
        $events = collect(implode(gzfile($file->getRealPath())))
            ->flatMap(fn(string $line) => json_decode(trim($line), true));

        if ($events->isEmpty()) {
            return response('ok (empty file)', 200)
                ->header('Content-Type', 'text/plain');
        }

        \App\Events\IngestHoneypotsEvents::dispatch($timestamp, $dns, $events->toArray());

        return response("ok ({$events->count()} events in file)", 200)
            ->header('Content-Type', 'text/plain');
    });
    Route::post('ports-scan/{uuid}', function (string $uuid, \Illuminate\Http\Request $request) {

        /** @var Scan $scan */
        $scan = Scan::where('ports_scan_id', $uuid)->first();

        if (!$scan) {
            return response('Unknown scan', 500)
                ->header('Content-Type', 'text/plain');
        }

        /** @var Asset $asset */
        $asset = $scan->asset()->first();

        if (!$asset) {
            return response('Unknown asset', 500)
                ->header('Content-Type', 'text/plain');
        }
        if ($request->has('task_result')) {
            EndPortsScan::dispatch(Carbon::now(), $asset, $scan, $request->get('task_result', []));
        } else {
            /* BEGIN COPY/PASTE FROM EndPortsScanListener.php */

            // Legacy stuff: if no port is open, create a dummy one that will be marked as closed by the vulns scanner
            $port = Port::create([
                'scan_id' => $scan->id,
                'hostname' => "localhost",
                'ip' => "127.0.0.1",
                'port' => 666,
                'protocol' => "tcp",
            ]);

            $scan->ports_scan_ends_at = \Carbon\Carbon::now();
            $scan->save();

            BeginVulnsScan::dispatch($scan, $port);

            /* END COPY/PASTE FROM EndPortsScanListener.php */
        }
        return response("ok", 200)
            ->header('Content-Type', 'text/plain');
    });
    Route::post('vulns-scan/{uuid}', function (string $uuid, \Illuminate\Http\Request $request) {

        if (!$request->has('task_result')) {
            return response('Missing task result', 500)
                ->header('Content-Type', 'text/plain');
        }

        /** @var Scan $scan */
        $scan = Scan::where('vulns_scan_id', $uuid)->first();

        if (!$scan) {
            return response('Unknown scan', 500)
                ->header('Content-Type', 'text/plain');
        }

        EndVulnsScan::dispatch(Carbon::now(), $scan, $request->get('task_result', []));

        return response("ok", 200)
            ->header('Content-Type', 'text/plain');
    });
})->middleware(['throttle:480,1']);

Route::middleware('auth:api')->get('/v2/public/whoami', fn(Request $request) => Auth::user());

Route::middleware('auth:api')->post('/tables/tsv/upload', [TablesUploadController::class, 'upload']);

Route::group(['prefix' => 'v2', 'as' => 'v2.'], function () {

    /** PUBLIC ENDPOINTS */
    Route::group(['prefix' => 'public', 'as' => 'public.'], function () {

        Route::get('/docs', function (Request $request) {
            if (Storage::exists('/public/docs/docs.public.html')) {
                return response(Storage::get('/public/docs/docs.public.html'))
                    ->header('Content-Type', 'text/html')
                    ->header('Cache-Control', 'public, max-age=3600');
            }
            return response()->json(['error' => 'Fichier HTML non trouvé.'], 404);
        })->name('rpc.docs');

        Route::rpc('/endpoint', [
            // TODO
        ])
            ->name('rpc.endpoint')
            ->middleware([
                GzipCompress::class,
                \App\Http\Middleware\LogJsonRpcRequests::class,
            ]);
    });

    /** PRIVATE ENDPOINTS */
    Route::group(['prefix' => 'private', 'as' => 'private.'], function () {

        Route::get('/whoami', fn(Request $request) => Auth::user())->name('whoami')
            ->middleware([\App\Http\Middleware\Authenticate::class]);

        Route::get('/docs', function (Request $request) {
            if (Storage::exists('/public/docs/docs.private.html')) {
                return response(Storage::get('/public/docs/docs.private.html'))
                    ->header('Content-Type', 'text/html')
                    ->header('Cache-Control', 'public, max-age=3600');
            }
            return response()->json(['error' => 'Fichier HTML non trouvé.'], 404);
        })->name('rpc.docs');

        Route::rpc('/endpoint', [
            \App\Http\Procedures\ApplicationsProcedure::class,
            \App\Http\Procedures\AssetsProcedure::class,
            \App\Http\Procedures\ChunksProcedure::class,
            \App\Http\Procedures\CollectionsProcedure::class,
            \App\Http\Procedures\CyberBuddyProcedure::class,
            \App\Http\Procedures\CyberScribeProcedure::class,
            \App\Http\Procedures\EventsProcedure::class,
            \App\Http\Procedures\FilesProcedure::class,
            \App\Http\Procedures\FrameworksProcedure::class,
            \App\Http\Procedures\HoneypotsProcedure::class,
            \App\Http\Procedures\InvitationsProcedure::class,
            \App\Http\Procedures\NotesProcedure::class,
            \App\Http\Procedures\PromptsProcedure::class,
            \App\Http\Procedures\OsqueryRulesProcedure::class,
            \App\Http\Procedures\OssecRulesProcedure::class,
            \App\Http\Procedures\RolesProcedure::class,
            \App\Http\Procedures\ServersProcedure::class,
            \App\Http\Procedures\TablesProcedure::class,
            \App\Http\Procedures\TheCyberBriefProcedure::class,
            \App\Http\Procedures\TracesProcedure::class,
            \App\Http\Procedures\UsersProcedure::class,
            \App\Http\Procedures\VulnerabilitiesProcedure::class,
        ])
            ->name('rpc.endpoint')
            ->middleware([
                GzipCompress::class,
                \App\Http\Middleware\LogJsonRpcRequests::class,
                \App\Http\Middleware\Authenticate::class,
                \App\Http\Middleware\CheckPermissionsJsonRpcRequest::class,
            ]);
    });
});
