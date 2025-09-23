<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolesPermissionsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('theme::iframes.roles-and-permissions', [
            'permissions' => Permission::query()->orderBy('name')->pluck('name')->toArray(),
        ]);
    }
}
