<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsValidIpAddress implements ValidationRule
{
    public static function test(?string $asset): bool
    {
        // See: https://www.php.net/manual/en/filter.constants.php#constant.filter-validate-ip
        return $asset && filter_var($asset, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) !== false;
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
