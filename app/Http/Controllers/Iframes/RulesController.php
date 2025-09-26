<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\OsqueryRulesProcedure;
use App\Http\Requests\JsonRpcRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RulesController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('theme::iframes.rules', (new OsqueryRulesProcedure())->list(JsonRpcRequest::createFrom($request)));
    }
}
