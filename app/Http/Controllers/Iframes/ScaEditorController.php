<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Models\YnhOssecCheck;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScaEditorController extends Controller
{
    public function __invoke(Request $request): View
    {
        $params = $request->validate([
            'check_id' => 'nullable|integer|exists:ynh_ossec_checks,id',
        ]);
        $check = isset($params['check_id']) ? YnhOssecCheck::findOrFail($params['check_id']) : new YnhOssecCheck();
        return view('theme::iframes.sca-editor', [
            'check' => $check,
        ]);
    }
}
