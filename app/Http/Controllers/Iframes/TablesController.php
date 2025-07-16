<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TablesController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('theme::iframes.tables', []);
    }
}
