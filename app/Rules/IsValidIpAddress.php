<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsValidIpAddress implements ValidationRule
{
    public static function test(?string $asset): bool
    {
        return $asset && filter_var($asset, FILTER_VALIDATE_IP) !== false && $asset !== '127.0.0.1';
    }

    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!self::test($value)) {
            $fail('The :attribute is not a valid IP address.');
        }
    }
}
