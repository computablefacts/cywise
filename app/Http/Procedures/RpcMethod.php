<?php

declare(strict_types=1);

namespace App\Http\Procedures;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RpcMethod extends \Sajya\Server\Attributes\RpcMethod
{
    public function __construct(
        ?string       $description = null,
        ?array        $params = null,
        ?array        $result = null,
        public ?array $examples = null, // array of strings : one row = one example
    )
    {
        parent::__construct($description, $params, $result);
    }
}
