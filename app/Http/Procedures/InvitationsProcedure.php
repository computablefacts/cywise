<?php

namespace App\Http\Procedures;

use App\Events\SendInvitation;
use App\Http\Requests\JsonRpcRequest;
use App\Models\User;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

/** @deprecated */
class InvitationsProcedure extends Procedure
{
    public static string $name = 'invitations';

    #[RpcMethod(
        description: "Create a single invitation, but do not send it.",
        params: [
            "fullname" => "The user name.",
            "email" => "The user email address.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function create(JsonRpcRequest $request): array
    {
        if (!$request->exists('users')) {

            $params = $request->validate([
                'fullname' => 'required|string|min:1|max:50',
                'email' => 'required|email',
            ]);

            $fullname = $params['fullname'];
            $email = $params['email'];
            $invitation = InvitationProxy::createInvitation($email, $fullname);

            return [
                'msg' => 'The invitation has been created!'
            ];
        }

        $params = $request->validate([
            'users.*' => 'required|array|min:1|max:500',
            'users.*.name' => 'required|string|min:1|max:50',
            'users.*.email' => 'required|email',
        ]);

        /** @var array $users */
        $users = $params['users'];

        foreach ($users as $user) {

            /** @var string $name */
            $name = $user['name'];
            /** @var string $email */
            $email = $user['email'];

            if (!InvitationProxy::where('email', $email)->exists()) {
                if (!User::where('email', $email)->exists()) {
                    $invitation = InvitationProxy::createInvitation($email, $name);
                }
            }
        }
        return [
            'msg' => 'The invitations have been created!'
        ];
    }

    #[RpcMethod(
        description: "Send a previously created invitation.",
        params: [
            "id" => "The invitation id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function send(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'id' => 'required|integer|exists:invitations,id',
        ]);

        $invitation = Invitation::where('id', $params['id'])->firstOrFail();
        SendInvitation::dispatch($invitation);

        return [
            'msg' => 'The invitation has been sent!'
        ];
    }
}
