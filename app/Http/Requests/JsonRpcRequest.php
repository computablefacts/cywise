<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class JsonRpcRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $payload = json_decode($this->getContent(), true);
        $procedure = Str::lower(Str::before($payload['method'], '@'));
        $method = Str::lower(Str::after($payload['method'], '@'));
        return $this->user()->canCall($procedure, $method);
    }
}
