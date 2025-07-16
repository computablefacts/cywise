<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\CollectionsProcedure;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectionsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $collections = (new CollectionsProcedure())->list($request);
        return view('theme::iframes.collections', [
            'collections' => $collections['collections'],
            'nbPages' => $collections['nb_pages'],
            'currentPage' => $collections['page'],
            'pagesSize' => $collections['page_size'],
        ]);
    }
}
