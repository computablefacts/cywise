<?php

namespace App\Helpers\Agents;

use App\Models\User;

/** @deprecated */
class Help extends AbstractAction
{
    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "help",
                "description" => "The list of structured commands available to the user.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [],
                    "required" => [],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct(User $user, string $threadId, array $args = [])
    {
        parent::__construct($user, $threadId, $args);
    }

    function execute(): AbstractAction
    {
        $this->output = "
# Commands Available To You
/monitor {begin|end} {domain|ip address}
/discover {domain}
/list {assets|open_ports|vulnerabilities}
        ";
        return $this;
    }

    public function memoize(): bool
    {
        return false;
    }
}
