<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\TracesProcedure;
use App\Http\Requests\JsonRpcRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TracesController extends Controller
{
    public function __invoke(Request $request): View
    {
        $traces = (new TracesProcedure())->list(JsonRpcRequest::createFrom($request));
        return view('theme::iframes.traces', $traces);
    }
}
