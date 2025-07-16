<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Models\YnhOssecCheck;
use App\Models\YnhOssecPolicy;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SCA = Security Checks Automation
 */
class ScaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $params = $request->validate([
            'check' => ['nullable', 'integer', 'exists:ynh_ossec_checks,id'],
            'policy' => ['nullable', 'string', 'exists:ynh_ossec_policies,uid'],
            'framework' => ['nullable', 'string', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'min:1', 'max:100'],
        ]);
        $checkId = empty($params['check']) || $params['check'] === 'null' ? null : (int)$params['check'];
        $policy = empty($params['policy']) || $params['policy'] === 'null' ? null : $params['policy'];
        $framework = empty($params['framework']) || $params['framework'] === 'null' ? null : $params['framework'];
        $search = empty($params['search']) || $params['search'] === 'null' ? null : $params['search'];
        $policies = YnhOssecPolicy::select('id', 'uid', 'name')->orderBy('name')->get();
        $checks = YnhOssecCheck::query()->with('policy');

        if ($policy) {
            $checks = $checks->whereIn('ynh_ossec_policy_id', $policies->filter(fn(YnhOssecPolicy $p) => $p->uid === $policy)->pluck('id'));
        } else {
            $checks = $checks->whereRaw('1=0');
        }
        if ($search) {
            $checks = $checks->whereFullText(['title', 'description', 'rationale', 'remediation'], $search);
        }

        $frameworks = $checks->get()
            ->flatMap(fn(YnhOssecCheck $check) => $check->frameworks())
            ->unique()
            ->sort()
            ->values();

        $checks = $checks
            ->whereRaw($checkId ? "uid={$checkId}" : '1=1')
            ->get()
            ->filter(fn(YnhOssecCheck $check) => !$framework || in_array($framework, $check->frameworks()))
            ->sort(fn(YnhOssecCheck $check1, YnhOssecCheck $check2) => strcmp($check1->title, $check2->title));

        return view('theme::iframes.sca', [
            'frameworks' => $frameworks,
            'checks' => $checks,
            'policies' => $policies,
            'search' => $search,
            'policy' => $policy,
            'framework' => $framework,
        ]);
    }
}
