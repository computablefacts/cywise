<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TableController extends Controller
{
    public function __invoke(Request $request): View
    {
        $params = $request->validate([
            'step' => ['nullable', 'integer', 'min:1'],
        ]);
        $step = $params['step'] ?? 1;
        return view('theme::iframes.table', [
            'step' => $step,
        ]);
    }
}
