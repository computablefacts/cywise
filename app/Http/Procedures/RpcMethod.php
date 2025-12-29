<?php

declare(strict_types=1);

namespace App\Http\Procedures;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RpcMethod extends \Sajya\Server\Attributes\RpcMethod
{
    public function __construct(
        ?string        $description = null,
        ?array         $params = null,
        ?array         $result = null,
        public ?array  $ai_examples = null, // array of strings : one row = one example
        public ?string $ai_result = null, // a template string that will be formatted with the result
    )
    {
        parent::__construct($description, $params, $result);
    }
}
