<?php

use App\Events\BeginVulnsScan;
use App\Events\EndPortsScan;
use App\Events\EndVulnsScan;
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
})->middleware(['throttle:240,1']);

Route::group([
    'prefix' => 'inventory',
], function () {
    Route::post('assets/discover', '\App\Http\Controllers\AssetController@discover');
    Route::post('assets/discover/from/ip', '\App\Http\Controllers\AssetController@discoverFromIp');
    Route::post('assets', '\App\Http\Controllers\AssetController@saveAsset');
    Route::get('assets', '\App\Http\Controllers\AssetController@userAssets');
    Route::post('asset/{asset}/monitoring/begin', '\App\Http\Controllers\AssetController@assetMonitoringBegins');
    Route::post('asset/{asset}/monitoring/end', '\App\Http\Controllers\AssetController@assetMonitoringEnds');
})->middleware(['auth:sanctum']);

Route::group([
    'prefix' => 'inbox',
], function () {
    Route::get('screenshot/{screenshot}', '\App\Http\Controllers\AssetController@screenshot');
})->middleware(['auth:sanctum']);

Route::group([
    'prefix' => 'facts',
], function () {
    Route::post('{asset}/metadata', '\App\Http\Controllers\AssetController@addTag');
    Route::delete('{asset}/metadata/{assetTag}', '\App\Http\Controllers\AssetController@removeTag');
})->middleware(['auth:sanctum']);

Route::group([
    'prefix' => 'adversary',
], function () {
    Route::delete('assets/{asset}', '\App\Http\Controllers\AssetController@deleteAsset');
    Route::post('assets/restart/{asset}', '\App\Http\Controllers\AssetController@restartScan');
    Route::get('infos-from-asset/{asset}', '\App\Http\Controllers\AssetController@infosFromAsset');
    Route::get('attacker-index', '\App\Http\Controllers\HoneypotController@attackerIndex');
    Route::get('recent-events', '\App\Http\Controllers\HoneypotController@recentEvents');
    Route::get('blacklist-ips/{attackerId?}', '\App\Http\Controllers\HoneypotController@blacklistIps');
    Route::get('vulnerabilities/{attackerId?}', '\App\Http\Controllers\HoneypotController@getVulnerabilitiesWithAssetInfo');
    Route::get('vulnerabilities2/{asset}', '\App\Http\Controllers\HoneypotController@getVulnerabilitiesWithAssetInfo2');
    Route::get('activity/{attacker}', '\App\Http\Controllers\HoneypotController@attackerActivity');
    Route::get('profile/{attacker}', '\App\Http\Controllers\HoneypotController@attackerProfile');
    Route::get('profile/stats/{attacker}', '\App\Http\Controllers\HoneypotController@attackerStats');
    Route::get('profile/tools/{attacker}', '\App\Http\Controllers\HoneypotController@attackerTools');
    Route::get('profile/competency/{attacker}', '\App\Http\Controllers\HoneypotController@attackerCompetency');
    Route::get('last/events/{attackerId?}', '\App\Http\Controllers\HoneypotController@getMostRecentEvent');
    Route::get('last/honeypots', '\App\Http\Controllers\HoneypotController@lastHoneypots');
    Route::get('honeypots/stats/{dns}', '\App\Http\Controllers\HoneypotController@getHoneypotEventStats');
    Route::get('alerts/stats', '\App\Http\Controllers\HoneypotController@getAlertStats');
    Route::get('honeypots/status', '\App\Http\Controllers\HoneypotController@honeypotsStatus');
    Route::get('assets/tags', '\App\Http\Controllers\HoneypotController@assetTags');
    Route::get('hashes', '\App\Http\Controllers\HoneypotController@getHashes');
    Route::post('hashes', '\App\Http\Controllers\HoneypotController@createHash');
    Route::delete('hashes/{assetHashTag}', '\App\Http\Controllers\HoneypotController@deleteHash');
    Route::post('hidden-alerts', '\App\Http\Controllers\HoneypotController@createHiddenAlert');
    Route::delete('hidden-alerts/{hiddenAlert}', '\App\Http\Controllers\HoneypotController@deleteHiddenAlert');
    Route::post('honeypots', '\App\Http\Controllers\HoneypotController@postHoneypots');
    Route::post('honeypots/set-next-step', '\App\Http\Controllers\HoneypotController@moveHoneypotsConfigurationToNextStep');
})->middleware(['auth:sanctum']);

Route::middleware('auth:api')->get('/v2/public/whoami', fn(Request $request) => Auth::user());

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
            \App\Http\Procedures\InvitationsProcedure::class,
            \App\Http\Procedures\NotesProcedure::class,
            \App\Http\Procedures\PromptsProcedure::class,
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
            ]);
    });
});
