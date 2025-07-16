<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Models\YnhFramework;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrameworksController extends Controller
{
    public function __invoke(Request $request): View
    {
        $params = $request->validate([
            'search' => ['nullable', 'string', 'min:1', 'max:100'],
        ]);
        $search = empty($params['search']) || $params['search'] === 'null' ? null : $params['search'];
        $frameworks = YnhFramework::query()
            ->orderBy('provider')
            ->orderBy('name')
            ->get();

        if (empty($search)) {
            $highlights = collect();
        } else {
            $highlights = $frameworks
                ->map(function (YnhFramework $framework) use ($search) {
                    return (object)[
                        'framework' => $framework,
                        'highlights' => $framework->highlights(explode(' ', $search)),
                    ];
                })
                ->filter(fn(object $item) => count($item->highlights) > 0);
        }
        return view('theme::iframes.frameworks', [
            'search' => $search,
            'frameworks' => $frameworks,
            'highlights' => $highlights,
        ]);
    }
}
