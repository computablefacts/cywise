<?php

namespace App\Http\Controllers;

use App\Http\Procedures\ServersProcedure;
use App\Http\Requests\CreateBackupRequest;
use App\Http\Requests\CreateHostRequest;
use App\Http\Requests\DownloadBackupRequest;
use App\Http\Requests\JsonRpcRequest;
use App\Models\YnhBackup;
use App\Models\YnhServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** @deprecated */
class YnhServerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(YnhServer $server, Request $request)
    {
        $tab = $request->input('tab', 'events');
        $limit = $request->input('limit', 40);

        return view('_server', compact(
            'tab',
            'limit',
            'server',
        ));
    }

    public function create(CreateHostRequest $request)
    {
        $request->merge([
            'order_id' => $request->input('order', 0),
        ]);
        $server = (new ServersProcedure())->create(JsonRpcRequest::createFrom($request));
        $tab = 'settings';
        return view('_server', compact('server', 'tab'));
    }

    public function createBackup(YnhServer $server, CreateBackupRequest $request)
    {
        $request->merge(['server_id' => $server->id]);
        return response()->json(['success' => (new ServersProcedure())->createBackup(JsonRpcRequest::createFrom($request))]);
    }

    public function downloadBackup(YnhServer $server, YnhBackup $backup, DownloadBackupRequest $request)
    {
        if ($server->isFrozen()) {
            return response()->json(['error' => "The server configuration is frozen."]);
        }

        $serverTenantId = $server->user->tenant_id;
        $serverCustomerId = $server->user->customer_id;

        $backupTenantId = $backup->user->tenant_id;
        $backupCustomerId = $backup->user->customer_id;

        $userTenantId = Auth::user()->tenant_id;
        $userCustomerId = Auth::user()->customer_id;

        if ($userTenantId) {
            if ($serverTenantId != $backupTenantId || $userTenantId != $backupTenantId) {
                return response()->json(['error' => 'Not allowed.'], 401);
            }
        }
        if ($userCustomerId) {
            if ($serverCustomerId != $backupCustomerId || $userCustomerId != $backupCustomerId) {
                return response()->json(['error' => 'Not allowed.'], 401);
            }
        }
        if (!$backup->storage_path) {
            return response()->json(['error' => 'Missing storage path.'], 500);
        }

        $path = $backup->storage_path;

        if (!Storage::disk(config('filesystems.backups'))->exists($path)) {
            return response()->json(['error' => 'Missing file.'], 404);
        }

        $filename = Str::afterLast($path, '/');
        $filesize = Storage::disk(config('filesystems.backups'))->size($path);
        $filetype = Storage::disk(config('filesystems.backups'))->mimeType($path);
        $headers = [
            'Content-Type' => $filetype,
            'Content-Length' => $filesize,
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ];

        // See https://aws.amazon.com/fr/blogs/developer/amazon-s3-php-stream-wrapper/
        $env = config('app.env');
        $bucket = config('filesystems.disks.backups.bucket');
        $url = "s3://{$bucket}/{$env}/{$path}";
        $client = Storage::disk('s3')->getClient();
        $client->registerStreamWrapper();

        return response()->streamDownload(function () use ($url) {
            if (!($stream = fopen($url, 'r'))) {
                throw new \Exception("Could not open stream: {$url}");
            }
            while (!feof($stream)) {
                echo fread($stream, 1024);
            }
            fclose($stream);
        }, $filename, $headers);
    }
}