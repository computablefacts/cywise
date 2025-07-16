<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $users = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->when($user->customer_id, fn($query, $customerId) => $query->where('customer_id', $customerId))
            ->get()
            ->sortBy('fullname', SORT_NATURAL | SORT_FLAG_CASE);
        
        return view('theme::iframes.users', [
            'users' => $users,
        ]);
    }
}
