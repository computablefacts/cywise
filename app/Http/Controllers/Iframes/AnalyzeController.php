<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;

class AnalyzeController extends Controller
{
    public function __invoke()
    {
        return view('theme::pages.analyze');
    }
}
