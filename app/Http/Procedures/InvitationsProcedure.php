<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use App\Models\Invitation;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class InvitationsProcedure extends Procedure
{
    public static string $name = 'invitations';

    #[RpcMethod(
        description: "Create a single invitation and send it.",
        params: [
            "email" => "The user email address.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function create(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'email' => 'required|email|unique:users|unique:invitations',
        ]);

        $email = $params['email'];
        $sender = $request->user();

        $invitation = Invitation::generate($email, $sender);
        $invitation->sendEmail();

        return [
            'msg' => 'The invitation has been created and sent!'
        ];
    }
}
