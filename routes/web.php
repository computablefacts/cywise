<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Enums\OsqueryPlatformEnum;
use App\Events\RebuildLatestEventsCache;
use App\Events\RebuildPackagesList;
use App\Helpers\SshKeyPair;
use App\Http\Controllers\Iframes\AnalyzeController;
use App\Http\Controllers\Iframes\ChunksController;
use App\Http\Controllers\Iframes\CollectionsController;
use App\Http\Controllers\Iframes\CyberBuddyController;
use App\Http\Controllers\Iframes\CyberScribeController;
use App\Http\Controllers\Iframes\DashboardController;
use App\Http\Controllers\Iframes\DocumentationController;
use App\Http\Controllers\Iframes\DocumentsController;
use App\Http\Controllers\Iframes\FrameworksController;
use App\Http\Controllers\Iframes\PromptsController;
use App\Http\Controllers\Iframes\RolesPermissionsController;
use App\Http\Controllers\Iframes\RulesController;
use App\Http\Controllers\Iframes\RulesEditorController;
use App\Http\Controllers\Iframes\ScaController;
use App\Http\Controllers\Iframes\ScaEditorController;
use App\Http\Controllers\Iframes\TableController;
use App\Http\Controllers\Iframes\TablesController;
use App\Http\Controllers\Iframes\TimelineController;
use App\Http\Controllers\Iframes\TracesController;
use App\Http\Controllers\Iframes\UsersController;
use App\Http\Controllers\Iframes\UsersInvitationController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use App\Jobs\DownloadDebianSecurityBugTracker;
use App\Mail\MailCoachPerformaRequested;
use App\Models\YnhServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;
use Spatie\Health\Http\Controllers\SimpleHealthCheckController;
use Wave\Facades\Wave;

// Wave routes
Wave::routes();

// See https://devdojo.com/question/customizing-the-two-factor-authentication
Route::view('/auth/login', 'vendor/auth/pages/auth/login');
Route::view('/auth/register', 'vendor/auth/pages/auth/register');

// See https://devdojo.com/wave/docs/features/user-profiles
Route::redirect('profile/{username}', '/dashboard');

// Legacy URL. Redirect for SEO reasons.
Route::redirect('/the-cyber-brief', '/blog');
Route::redirect('/home', '/dashboard');

// Public facing tools
Route::get('/cyber-check', function (\Illuminate\Http\Request $request) {
    return redirect()->route('tools.cybercheck', [
        'hash' => Str::random(128),
        'step' => 1,
    ]);
})->name('tools.cybercheck.init');

Route::match(['get', 'post'], '/cyber-check/{hash}/{step}', [\App\Http\Controllers\ToolsController::class, 'cyberCheck'])
    ->name('tools.cybercheck');

Route::post('/cyber-check/discovery', [\App\Http\Controllers\ToolsController::class, 'discovery'])
    ->name('tools.discovery');

Route::get('/cyber-advisor', [\App\Http\Controllers\ToolsController::class, 'cyberAdvisor'])
    ->name('tools.cyberadvisor');

/**
 * Health check
 */
Route::get('check-health', [SimpleHealthCheckController::class, '__invoke']);
Route::get('check-health/ui', [HealthCheckResultsController::class, '__invoke'])->middleware('auth');

// Cywise routes
Route::get('/setup/script', function (\Illuminate\Http\Request $request) {

    $token = $request->input('api_token');

    if (!$token) {
        return response('Missing token', 403)
            ->header('Content-Type', 'text/plain');
    }

    /** @var \Laravel\Sanctum\PersonalAccessToken $token */
    $token = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

    if (!$token) {
        return response('Invalid token', 403)
            ->header('Content-Type', 'text/plain');
    }

    /** @var \App\Models\User $user */
    $user = $token->tokenable;
    Auth::login($user);

    $ip = $request->input('server_ip');

    if (!$ip) {
        return response('Invalid IP address', 500)
            ->header('Content-Type', 'text/plain');
    }

    $name = $request->input('server_name');

    if (!$name) {
        return response('Invalid server name', 500)
            ->header('Content-Type', 'text/plain');
    }

    $platform = $request->input('platform');

    if ($platform) {
        $platform = OsqueryPlatformEnum::tryFrom($platform);
    } else {
        $platform = OsqueryPlatformEnum::LINUX;
    }

    if (!$platform) {
        return response('Invalid platform name', 500)
            ->header('Content-Type', 'text/plain');
    }

    $server = \App\Models\YnhServer::where('ip_address', $ip)
        ->where('name', $name)
        ->first();

    if (!$server) {

        $server = \App\Models\YnhServer::where('ip_address_v6', $ip)
            ->where('name', $name)
            ->first();

        if (!$server) {

            $server = YnhServer::create([
                'name' => $name,
                'ip_address' => $ip,
                'user_id' => $user->id,
                'secret' => Str::random(30),
                'is_ready' => false,
                'is_frozen' => true,
                'added_with_curl' => true,
                'platform' => $platform,
            ]);

            \App\Events\CreateAsset::dispatch($user, $server->ip(), true, [$server->name]);
        }
    }
    if ($server->is_ready) {
        return response('The server is already configured', 500)
            ->header('Content-Type', 'text/plain');
    }
    if (!$server->secret) {
        $server->secret = Str::random(30);
    }
    if (!$server->ssh_port) {
        $server->ssh_port = 22;
    }
    if (!$server->ssh_username) {
        $server->ssh_username = 'twr_admin';
    }
    if (!$server->ssh_public_key || !$server->ssh_private_key) {

        $keys = new SshKeyPair();
        $keys->init();

        $server->ssh_public_key = $keys->publicKey();
        $server->ssh_private_key = $keys->privateKey();
    }

    $server->is_ready = true;
    $server->is_frozen = false;
    $server->added_with_curl = true;
    $server->save();

    // Send a Performa setup request if the tenant does not have a Performa domain yet
    if (!$user->performa_domain || !$user->performa_secret) {
        MailCoachPerformaRequested::sendEmail();
    }

    // 1. In the browser, go to "https://app.towerify.io" and login using your user account
    // 2. On the server, run as root: curl -s https://app.towerify.io/setup/script?api_token=<token>&server_ip=<ip>&server_name=<name> | bash
    $installScript = ($server->platform === OsqueryPlatformEnum::WINDOWS) ? \App\Models\YnhOsquery::monitorWindowsServer($server) : \App\Models\YnhOsquery::monitorLinuxServer($server);

    return response($installScript, 200)
        ->header('Content-Type', 'text/plain');
})->middleware(['throttle:6,1']);

Route::get('/update/{secret}', function (string $secret, \Illuminate\Http\Request $request) {

    $server = \App\Models\YnhServer::where('secret', $secret)->first();

    if (!$server) {
        return response('Unknown server', 500)
            ->header('Content-Type', 'text/plain');
    }

    $installScript = ($server->platform === OsqueryPlatformEnum::WINDOWS) ? \App\Models\YnhOsquery::monitorWindowsServer($server) : \App\Models\YnhOsquery::monitorLinuxServer($server);

    return response($installScript, 200)
        ->header('Content-Type', 'text/plain');
})->middleware('throttle:6,1');

Route::post('/logalert/{secret}', function (string $secret, \Illuminate\Http\Request $request) {

    // See https://github.com/jhuckaby/logalert?tab=readme-ov-file#web-hook-alerts for details
    $payload = $request->all();
    $validator = Validator::make(
        $payload,
        [
            'name' => 'required|string',
            'file' => 'required|string|min:1|max:255',
            'date' => 'required|string',
            'hostname' => 'required|string',
            'lines' => 'required|array|min:1|max:500',
        ]
    );
    if ($validator->fails()) {
        return new JsonResponse([
            'status' => 'failure',
            'message' => 'payload validation failed',
            'payload' => $payload,
        ], 200, ['Access-Control-Allow-Origin' => '*']);
    }
    try {

        $server = \App\Models\YnhServer::where('secret', $secret)->first();

        if (!$server) {
            return new JsonResponse([
                'status' => 'failure',
                'message' => 'server not found',
                'payload' => $payload,
            ], 200, ['Access-Control-Allow-Origin' => '*']);
        }

        $events = collect($request->input('lines'))
            ->map(fn($line) => $line ? json_decode($line, true) : [])
            ->filter(fn($event) => $event && count($event) > 0)
            ->all();

        \App\Events\ProcessLogalertPayload::dispatch($server, $events);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error($e);
        return new JsonResponse([
            'status' => 'failure',
            'message' => $e->getMessage(),
            'payload' => $payload,
        ], 200, ['Access-Control-Allow-Origin' => '*']);
    }
    return new JsonResponse(['status' => 'success'], 200, ['Access-Control-Allow-Origin' => '*']);
});

Route::get('/logalert/{secret}', function (string $secret, \Illuminate\Http\Request $request) {

    $server = \App\Models\YnhServer::where('secret', $secret)->first();

    if (!$server) {
        return response('Unknown server', 500)
            ->header('Content-Type', 'text/plain');
    }

    $config = json_encode(\App\Models\YnhOsquery::configLogAlert($server));

    return response($config, 200)
        ->header('Content-Type', 'text/plain');
})->middleware('throttle:6,1');

Route::get('/osquery/{secret}', function (string $secret, \Illuminate\Http\Request $request) {

    $server = \App\Models\YnhServer::where('secret', $secret)->first();

    if (!$server) {
        return response('Unknown server', 500)
            ->header('Content-Type', 'text/plain');
    }

    $config = json_encode(\App\Models\YnhOsquery::configOsquery());

    return response($config, 200)
        ->header('Content-Type', 'text/plain');
})->middleware('throttle:6,1');

Route::get('/localmetrics/{secret}', function (string $secret, \Illuminate\Http\Request $request) {

    $server = \App\Models\YnhServer::where('secret', $secret)->first();

    if (!$server) {
        return response('Unknown server', 500)
            ->header('Content-Type', 'text/plain');
    }

    $script = ($server->platform === OsqueryPlatformEnum::WINDOWS) ? '# Windows: no local metric' : '# Linux: no local metric';

    return response($script, 200)
        ->header('Content-Type', 'text/plain');
})->middleware('throttle:6,1');

Route::get('/logparser/{secret}', function (string $secret, \Illuminate\Http\Request $request) {

    $server = \App\Models\YnhServer::where('secret', $secret)->first();

    if (!$server) {
        return response('Unknown server', 500)
            ->header('Content-Type', 'text/plain');
    }

    $config = ($server->platform === OsqueryPlatformEnum::WINDOWS) ? \App\Models\YnhOsquery::configLogParserWindows($server) : \App\Models\YnhOsquery::configLogParserLinux($server);

    return response($config, 200)
        ->header('Content-Type', 'text/plain');
})->middleware('throttle:6,1');

Route::post('/logparser/{secret}', function (string $secret, \Illuminate\Http\Request $request) {

    if (!$request->hasFile('data')) {
        return response('Missing attachment', 500)
            ->header('Content-Type', 'text/plain');
    }

    $file = $request->file('data');

    if (!$file->isValid()) {
        return response('Invalid attachment', 500)
            ->header('Content-Type', 'text/plain');
    }

    $server = \App\Models\YnhServer::where('secret', $secret)->first();

    if (!$server) {
        return response('Unknown server', 500)
            ->header('Content-Type', 'text/plain');
    }

    $filename = $file->getClientOriginalName();

    if ($filename === "apache.txt.gz" || $filename === "nginx.txt.gz") {

        $logs = collect(gzfile($file->getRealPath()))
            ->map(fn(string $line) => Str::of(trim($line))->split('/\s+/'))
            ->filter(fn(Collection $lines) => $lines->count() === 3 && filter_var($lines->last(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6))
            ->map(fn(Collection $lines) => [
                'count' => $lines->first(),
                'service' => $lines->get(1),
                'ip' => YnhServer::expandIp($lines->last()),
            ]);

        if ($logs->isEmpty()) {
            return response('ok (empty file)', 200)
                ->header('Content-Type', 'text/plain');
        }

        // Do not chunk because logs are managed as a whole!
        \App\Events\ProcessLogparserPayload::dispatch($server, $logs);

    } else if ($filename === "osquery.jsonl.gz") {

        $logs = collect(gzfile($file->getRealPath()))
            ->map(fn(string $line) => json_decode(trim($line), true));

        if ($logs->isEmpty()) {
            return response('ok (empty file)', 200)
                ->header('Content-Type', 'text/plain');
        }

        $logs->chunk(1000)->each(function ($chunk) use ($server) {
            \App\Events\ProcessLogalertPayloadEx::dispatch($server, $chunk->toArray());
        });

    } else {
        return response('Invalid attachment', 500)
            ->header('Content-Type', 'text/plain');
    }
    return response("ok ({$logs->count()} rows in file)", 200)
        ->header('Content-Type', 'text/plain');
});

Route::get('/performa/{secret}', function (string $secret, \Illuminate\Http\Request $request) {

    $server = \App\Models\YnhServer::where('secret', $secret)->first();

    if (!$server) {
        return response('Unknown server', 500)
            ->header('Content-Type', 'text/plain');
    }

    $config = \App\Models\YnhOsquery::configPerforma($server);

    return response($config, 200)
        ->header('Content-Type', 'text/plain');
})->middleware('throttle:6,1');

Route::get('/performa/user/login/{performa_domain}', function (string $performa_domain, \Illuminate\Http\Request $request) {

    $shouldLogout = $request->input('logout', 0);
    if (1 == $shouldLogout) {
        Auth::logout();
        return redirect()->route('home');
    }

    /** @var User $user */
    $user = Auth::user();
    $usernames = collect(config('towerify.performa.whitelist.usernames'))->map(fn(string $username) => $username . '@')->toArray();
    $domains = collect(config('towerify.performa.whitelist.domains'))->map(fn(string $domain) => '@' . $domain)->toArray();

    if ($user
        && ($user->performa_domain === $performa_domain
            || (Str::startsWith($user->email, $usernames) && Str::endsWith($user->email, $domains))
        )
    ) {
        return response()->json([
            'code' => 0,
            'username' => $user->ynhUsername(),
            'user' => [
                'full_name' => $user->name,
                'email' => $user->email,
                'privileges' => [
                    'admin' => 1,
                ],
            ],
        ]);
    }

    return response()->json([
        'code' => 0,
        'location' => route('login') . '?redirect_to=',
    ]);

})->middleware('throttle:6,1');

/** @deprecated */
Route::get('/dispatch/job/{job}/{trialId?}', function (string $job, ?int $trialId = null) {

    /** @var User $user */
    $user = Auth::user();
    $usernames = collect(config('towerify.telescope.whitelist.usernames'))->map(fn(string $username) => $username . '@')->toArray();
    $domains = collect(config('towerify.telescope.whitelist.domains'))->map(fn(string $domain) => '@' . $domain)->toArray();

    if (Str::startsWith($user->email, $usernames) && Str::endsWith($user->email, $domains)) {
        try {
            if ($job === 'dl_debian_security_bug_tracker') {
                DownloadDebianSecurityBugTracker::dispatch();
            } elseif ($job === 'rebuild_packages_list') {
                RebuildPackagesList::sink();
            } elseif ($job === 'rebuild_latest_events_cache') {
                RebuildLatestEventsCache::sink();
            } elseif ($job === 'send_audit_report') {
                $user = \App\Models\User::where('email', config('towerify.admin.email'))->firstOrFail();
                $event = new \App\Events\SendAuditReport($user, false);
                (new \App\Listeners\SendAuditReportListener())->handle($event);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
        return response('ok', 200)->header('Content-Type', 'text/plain');
    }
    return response('Unauthorized', 403)->header('Content-Type', 'text/plain');
})->middleware('auth');

Route::post('/llm1', '\App\Http\Controllers\CyberBuddyController@llm1')->middleware('auth');

Route::post('/llm2', '\App\Http\Controllers\CyberBuddyController@llm2')->middleware('auth');

Route::get('/templates', '\App\Http\Controllers\CyberBuddyController@templates')->middleware('auth');

Route::post('/templates', '\App\Http\Controllers\CyberBuddyController@saveTemplate')->middleware('auth');

Route::delete('/templates/{id}', '\App\Http\Controllers\CyberBuddyController@deleteTemplate')->middleware('auth');

Route::get('/files/stream/{secret}', '\App\Http\Controllers\CyberBuddyController@streamFile');

Route::get('/files/download/{secret}', '\App\Http\Controllers\CyberBuddyController@downloadFile');

Route::post('/files/one', '\App\Http\Controllers\CyberBuddyController@uploadOneFile')->middleware('auth:sanctum');

Route::post('/files/many', '\App\Http\Controllers\CyberBuddyController@uploadManyFiles')->middleware('auth:sanctum');

Route::delete('/conversations/{id}', '\App\Http\Controllers\CyberBuddyController@deleteConversation')->middleware('auth');

Route::delete('/frameworks/{id}', '\App\Http\Controllers\CyberBuddyController@unloadFramework')->middleware('auth');

Route::post('/frameworks/{id}', '\App\Http\Controllers\CyberBuddyController@loadFramework')->middleware('auth');

Route::middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class])->prefix('iframes')->name('iframes.')->group(function () {
    Route::get('/analyze', [AnalyzeController::class, '__invoke'])->name('analyze');
    Route::get('/assets', [TimelineController::class, '__invoke'])->name('assets');
    Route::get('/chunks', [ChunksController::class, '__invoke'])->name('chunks');
    Route::get('/collections', [CollectionsController::class, '__invoke'])->name('collections');
    Route::get('/conversations', [TimelineController::class, '__invoke'])->name('conversations');
    Route::get('/cyberbuddy', [CyberBuddyController::class, '__invoke'])->name('cyberbuddy');
    Route::get('/cyberscribe', [CyberScribeController::class, '__invoke'])->name('cyberscribe');
    Route::get('/dashboard', [DashboardController::class, '__invoke'])->name('dashboard');
    Route::get('/documentation', [DocumentationController::class, '__invoke'])->name('documentation');
    Route::get('/documents', [DocumentsController::class, '__invoke'])->name('documents');
    Route::get('/events', [TimelineController::class, '__invoke'])->name('events');
    Route::get('/frameworks', [FrameworksController::class, '__invoke'])->name('frameworks');
    Route::get('/ioc', [TimelineController::class, '__invoke'])->name('ioc');
    Route::get('/leaks', [TimelineController::class, '__invoke'])->name('leaks');
    Route::get('/notes-and-memos', [TimelineController::class, '__invoke'])->name('notes-and-memos');
    Route::get('/prompts', [PromptsController::class, '__invoke'])->name('prompts');
    Route::get('/roles-and-permissions', [RolesPermissionsController::class, '__invoke'])->name('roles-and-permissions');
    Route::get('/rules', [RulesController::class, '__invoke'])->name('rules');
    Route::get('/rules/edit', [RulesEditorController::class, '__invoke'])->name('rules-editor');
    Route::get('/sca', [ScaController::class, '__invoke'])->name('sca');
    Route::get('/sca/edit', [ScaEditorController::class, '__invoke'])->name('sca-editor');
    Route::get('/table', [TableController::class, '__invoke'])->name('table');
    Route::get('/tables', [TablesController::class, '__invoke'])->name('tables');
    Route::get('/traces', [TracesController::class, '__invoke'])->name('traces');
    Route::get('/users', [UsersController::class, '__invoke'])->name('users');
    Route::get('/users/invitation', [UsersInvitationController::class, '__invoke'])->name('user-invitation');
    Route::get('/vulnerabilities', [TimelineController::class, '__invoke'])->name('vulnerabilities');
});
