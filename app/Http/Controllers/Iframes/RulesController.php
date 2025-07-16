<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Models\YnhOsqueryRule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RulesController extends Controller
{
    public function __invoke(Request $request): View
    {
        $rules = YnhOsqueryRule::where('enabled', true)
            ->get()
            ->map(function (YnhOsqueryRule $rule) {
                $rule->name = Str::after($rule->name, 'cywise_');
                return $rule;
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        return view('theme::iframes.rules', [
            'rules' => $rules,
        ]);
    }
}
