<?php

namespace App\Http\Procedures;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class RolesProcedure extends Procedure
{
    public static string $name = 'roles';

    #[RpcMethod(
        description: "Add a permission to a role.",
        params: [
            'role' => 'Role name (string) or ID (integer).',
            'permission' => 'Permission name (string) or ID (integer).',
        ],
        result: [
            'msg' => 'Success message.',
        ]
    )]
    public function addPermission(Request $request): array
    {
        $user = $request->user();

        if (!$user || !$user->isCywiseAdmin()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'role' => ['required'],
            'permission' => ['required'],
        ]);

        $role = is_numeric($params['role'])
            ? Role::query()->find((int)$params['role'])
            : Role::query()->where('name', $params['role'])->first();

        if (!$role) {
            throw new \Exception('Role not found.');
        }

        $permission = is_numeric($params['permission'])
            ? Permission::query()->find((int)$params['permission'])
            : Permission::query()->where('name', $params['permission'])->first();

        if (!$permission) {
            throw new \Exception('Permission not found.');
        }

        $role->givePermissionTo($permission->name);

        return [
            'msg' => "Permission '{$permission->name}' added to role '{$role->name}'.",
        ];
    }

    #[RpcMethod(
        description: "Remove a permission from a role.",
        params: [
            'role' => 'Role name (string) or ID (integer).',
            'permission' => 'Permission name (string) or ID (integer).',
        ],
        result: [
            'msg' => 'Success message.',
        ]
    )]
    public function removePermission(Request $request): array
    {
        $user = $request->user();
        if (!$user || !$user->isCywiseAdmin()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'role' => ['required'],
            'permission' => ['required'],
        ]);

        $role = is_numeric($params['role'])
            ? Role::query()->find((int)$params['role'])
            : Role::query()->where('name', $params['role'])->first();

        if (!$role) {
            throw new \Exception('Role not found.');
        }

        $permission = is_numeric($params['permission'])
            ? Permission::query()->find((int)$params['permission'])
            : Permission::query()->where('name', $params['permission'])->first();

        if (!$permission) {
            throw new \Exception('Permission not found.');
        }

        $role->revokePermissionTo($permission->name);

        return [
            'msg' => "Permission '{$permission->name}' removed from role '{$role->name}'.",
        ];
    }

    #[RpcMethod(
        description: "List all roles with their permissions (permissions sorted alphabetically).",
        params: [],
        result: [
            'roles' => 'Array of roles with permissions',
        ]
    )]
    public function list(Request $request): array
    {
        return [
            'roles' => Role::query()
                ->orderBy('name')
                ->get()
                ->map(function (Role $role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'permissions' => $role->permissions
                            ->pluck('name')
                            ->sort()
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
