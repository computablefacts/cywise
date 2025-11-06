<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsValidRange implements ValidationRule
{
    public static function test(?string $asset): bool
    {
        if ($asset) {

            $parts = explode('/', $asset);

            if (count($parts) === 2) {

                $ip = $parts[0];
                $subnet = filter_var($parts[1], FILTER_VALIDATE_INT, ['default' => 'Not an integer']);

                // See: https://www.php.net/manual/en/filter.constants.php#constant.filter-validate-ip
                $ipV6 = filter_var($ip, FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE]);
                $ipV4 = filter_var($ip, FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE]);

                if ($ipV6 !== false) {
                    return is_int($subnet) && $subnet >= 120 && $subnet <= 128;
                }

                if ($ipV4 !== false) {
                    return is_int($subnet) && $subnet >= 24 && $subnet <= 32;
                }

                return false;
            }
        }
        return false;
    }

    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!self::test($value)) {
            $fail('The :attribute is not a valid range of IP or contains more than 256 addresses.');
        }
    }
}
