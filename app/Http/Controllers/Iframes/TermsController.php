<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Parsedown;

class TermsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $file = file_exists(public_path('/cywise/markdown/terms.' . app()->getLocale() . '.md'))
            ? public_path('/cywise/markdown/terms.' . app()->getLocale() . '.md')
            : public_path('/cywise/markdown/terms.md');

        return view('theme::iframes.markdown', [
            'html' => (new Parsedown)->text(file_get_contents($file))
        ]);
    }
}
