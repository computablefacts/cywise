<?php

namespace App\Http\Controllers\Iframes;

use App\AgentSquad\ActionsRegistry;
use App\Http\Controllers\Controller;
use App\Models\ActionSetting;
use App\Models\User;
use Illuminate\Http\Request;

class ActionsController extends Controller
{
    public function __invoke(Request $request)
    {
        $actions = ActionsRegistry::all();
        $user = $request->user();
        $tenantSettings = ActionSetting::query()
            ->where('scope_type', 'tenant')
            ->where('scope_id', $user->tenant_id)
            ->get()
            ->keyBy('action');
        $userIdSelected = $request->integer('user_id');
        $userSelected = null;
        $userSettings = collect();

        if ($userIdSelected) {

            $userSelected = User::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('id', $userIdSelected)
                ->first();

            if ($userSelected) {
                $userSettings = ActionSetting::query()
                    ->where('scope_type', 'user')
                    ->where('scope_id', $userSelected->id)
                    ->get()
                    ->keyBy('action');
            }
        }

        $users = User::where('tenant_id', $user->tenant_id)->orderBy('name')->get();

        return view('theme::iframes.actions', compact('actions', 'tenantSettings', 'userSettings', 'users', 'userSelected'));
    }
}
