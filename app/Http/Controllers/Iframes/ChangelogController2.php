<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ChangelogController2 extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('theme::iframes.changelog2', [
            'logs' => \Wave\Changelog::orderBy('created_at', 'DESC')->limit(15)->get()
        ]);
    }
}
