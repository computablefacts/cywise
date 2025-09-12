<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Models\YnhOsqueryRule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RuleEditorController extends Controller
{
    public function __invoke(Request $request): View
    {
        $params = $request->validate([
            'rule_id' => 'nullable|integer|exists:ynh_osquery_rules,id',
        ]);
        $rule = isset($params['rule_id']) ? YnhOsqueryRule::findOrFail($params['rule_id']) : new YnhOsqueryRule();
        return view('theme::iframes.rules-editor', [
            'rule' => $rule,
        ]);
    }
}
