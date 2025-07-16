<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\PromptsProcedure;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromptsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $prompts = (new PromptsProcedure())->list($request);
        return view('theme::iframes.prompts', [
            'prompts' => $prompts['prompts'],
            'nbPages' => $prompts['nb_pages'],
            'currentPage' => $prompts['page'],
            'pagesSize' => $prompts['page_size'],
        ]);
    }
}
