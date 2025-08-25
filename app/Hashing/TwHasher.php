<?php

namespace App\Hashing;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Hashing\AbstractHasher;

/** @deprecated */
class TwHasher extends AbstractHasher implements Hasher
{
    public static function hash($value): string
    {
        return self::cywise_hash($value);
    }

    public static function unhash($value): string
    {
        return self::cywise_unhash($value);
    }

    // Keep in sync with index.php
    private static function cywise_random_string(int $length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ&?!#';
        $lengthCharacters = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, $lengthCharacters - 1);
            $randomString .= $characters[$index];
        }
        return $randomString;
    }

    // Keep in sync with Helpers.php
    private static function cywise_hash(string $value): string
    {
        $key = config('towerify.hasher.nonce');
        $initializationVector = self::cywise_random_string(16);
        return $initializationVector . '_' . openssl_encrypt($value, 'AES-256-CBC', $key, 0, $initializationVector);
    }

    // Keep in sync with Helpers.php
    private static function cywise_unhash(string $value): string
    {
        $key = config('towerify.hasher.nonce');
        $initializationVector = strtok($value, '_');
        $value2 = substr($value, strpos($value, '_') + 1);
        return openssl_decrypt($value2, 'AES-256-CBC', $key, 0, $initializationVector);
    }

    public function make($value, array $options = [])
    {
        return self::hash($value);
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        return false;
    }

    public function check($value, $hashedValue, array $options = []): bool
    {
        return $value === self::unhash($hashedValue);
    }
}
