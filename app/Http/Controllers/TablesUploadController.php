<?php

namespace App\Http\Controllers;

use App\Rules\IsValidTableType;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TablesUploadController extends Controller
{
    public function upload(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $params = $request->validate([
            'files' => 'required',
            'files.*' => [
                'required',
                'file',
                'max:102400', // up to ~100Mb per file
                new IsValidTableType(),
            ],
        ]);

        $input = "{$user->tenant_id}/{$user->id}/";

        /** @var UploadedFile $file */
        foreach ($params['files'] as $file) {

            $original = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $ext = $file->getClientOriginalExtension();

            if ($ext !== 'tsv') {
                $original .= '.tsv'; // Coerce to .tsv if content-type passed validation but extension isn't .tsv
            }

            $storage = Storage::disk('tables-s3');
            $filename = "{$original}.{$ext}";

            if (!$storage->exists($input)) {
                if (!$storage->makeDirectory($input)) {
                    return response()->json(['error' => 'Directory cannot be created.'], 500);
                }
            }
            if (!$storage->putFileAs($input, $file, $filename)) {
                return response()->json(['error' => 'File cannot be imported.'], 500);
            }
        }
        return response()->json([
            'message' => 'Files uploaded successfully.',
        ]);
    }
}
