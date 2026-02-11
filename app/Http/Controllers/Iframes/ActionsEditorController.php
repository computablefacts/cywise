<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Models\RemoteAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActionsEditorController extends Controller
{
    public function __invoke(Request $request): View
    {
        $params = $request->validate([
            'action_id' => 'nullable|integer|exists:cb_remote_actions,id',
        ]);
        $action = isset($params['action_id']) ? RemoteAction::findOrFail($params['action_id']) : new RemoteAction();
        return view('theme::iframes.actions-editor', [
            'action' => $action,
        ]);
    }
}
