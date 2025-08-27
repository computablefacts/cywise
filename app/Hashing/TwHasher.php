<?php

namespace App\Hashing;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Hashing\AbstractHasher;

/** @deprecated */
class TwHasher extends AbstractHasher implements Hasher
{
    public static function hash($value): string
    {
        return cywise_hash($value);
    }

    public static function unhash($value): string
    {
        return cywise_unhash($value);
    }

    public function make($value, array $options = [])
    {
        return cywise_hash($value);
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        return false;
    }

    public function check($value, $hashedValue, array $options = []): bool
    {
        return $value === cywise_unhash($hashedValue);
    }
}
