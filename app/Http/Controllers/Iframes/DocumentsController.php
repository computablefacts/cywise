<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\FilesProcedure;
use App\Http\Requests\JsonRpcRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $files = (new FilesProcedure())->list(JsonRpcRequest::createFrom($request));
        return view('theme::iframes.documents', [
            'files' => $files['files'],
            'collection' => $files['collection'],
            'nbPages' => $files['nb_pages'],
            'currentPage' => $files['page'],
            'pagesSize' => $files['page_size'],
        ]);
    }
}
