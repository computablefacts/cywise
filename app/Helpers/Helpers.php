<?php

use Illuminate\Support\Str;

if (!function_exists('format_bytes')) {
    function format_bytes($bytes, $precision = 2)
    {
        $units = array(' B', ' KiB', ' MiB', ' GiB', ' TiB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . $units[$pow];
    }
}
if (!function_exists('format_number')) {
    function format_number(int $number, int $precision = 1): array
    {
        if ($number < 1000) {
            return [(string)$number, ''];
        }
        $divisors = [
            pow(1000, 4) => 'T',
            pow(1000, 3) => 'Md',
            pow(1000, 2) => 'M',
            pow(1000, 1) => 'K',
        ];
        foreach ($divisors as $divisor => $suffix) {
            if ($number >= $divisor) {
                $val = $number / $divisor;
                $rounded = round($val, $precision);
                if ($rounded == (int)$rounded) {
                    return [(int)$rounded, $suffix];
                }
                return [$rounded, $suffix];
            }
        }
        return [(string)$number, ''];
    }
}
if (!function_exists('app_url')) {
    function app_url(): string
    {
        return rtrim(config('app.url'), '/');
    }
}
if (!function_exists('is_cywise')) {
    function is_cywise(): bool
    {
        return mb_strtolower(config('app.name')) === 'cywise';
    }
}
if (!function_exists('cywise_random_string')) {
    function cywise_random_string(int $length): string
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
}
if (!function_exists('cywise_hash')) {
    function cywise_hash(string $value): string
    {
        return cywise_hash_ext(config('towerify.hasher.nonce'), $value);
    }
}
if (!function_exists('cywise_unhash')) {
    function cywise_unhash(string $value): string
    {
        return cywise_unhash_ext(config('towerify.hasher.nonce'), $value);
    }
}
if (!function_exists('cywise_hash_ext')) {
    function cywise_hash_ext(string $key, string $value): string
    {
        $initializationVector = cywise_random_string(16);
        return $initializationVector . '_' . openssl_encrypt($value, 'AES-256-CBC', $key, 0, $initializationVector);
    }
}
if (!function_exists('cywise_unhash_ext')) {
    function cywise_unhash_ext(string $key, string $value): string
    {
        $initializationVector = strtok($value, '_');
        $value2 = substr($value, strpos($value, '_') + 1);
        return openssl_decrypt($value2, 'AES-256-CBC', $key, 0, $initializationVector);
    }
}
if (!function_exists('cywise_encrypt_file')) {
    function cywise_encrypt_file(string $key, string $file): string
    {
        $content = file_get_contents($file);
        $contentCompressed = gzcompress($content);

        if ($contentCompressed === false) {
            throw new \Exception("Failed to compress file '{$file}'");
        }

        $contentEncrypted = cywise_hash_ext($key, $contentCompressed);

        if ($contentEncrypted === false) {
            throw new \Exception("Failed to encrypt file '{$file}'");
        }

        $fileTmp = tempnam(sys_get_temp_dir(), 'legal_');
        file_put_contents($fileTmp, $contentEncrypted);

        return $fileTmp;
    }
}
if (!function_exists('cywise_decrypt_file')) {
    function cywise_decrypt_file(string $key, string $file): string
    {
        if (!is_file($file)) {
            throw new \Exception("File '{$file}' does not exist");
        }

        $content = file_get_contents($file);
        $contentDecrypted = cywise_unhash_ext($key, $content);

        if ($contentDecrypted === false) {
            throw new \Exception("Failed to decrypt file '{$file}'");
        }

        $contentDecompressed = gzuncompress($contentDecrypted);

        if ($contentDecompressed === false) {
            throw new \Exception("Failed to decompress file '{$file}'");
        }

        $fileTmp = tempnam(sys_get_temp_dir(), 'legal_');
        file_put_contents($fileTmp, $contentDecompressed);

        return $fileTmp;
    }
}
if (!function_exists('app_config_override')) {
    function app_config_override(): array
    {
        $database = config('database.default');
        $config = config('database.connections.' . $database);

        try {
            if ($database == 'sqlite') {
                $dsn = "{$config['driver']}:{$config['database']}";
                $pdo = new PDO($dsn);
            } else {
                $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                $pdo = new PDO($dsn, $config['username'], $config['password']);
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $statement = $pdo->query("SELECT `key`, `value`, `is_encrypted` FROM app_config");
            $settings = $statement->fetchAll(PDO::FETCH_ASSOC);
            $pdo = null;

            foreach ($settings as $keyValuePair) {
                $key = $keyValuePair['key'];
                $value = $keyValuePair['value'];
                if (Str::startsWith($key, 'array:')) {
                    $key = Str::chopStart($key, 'array:');
                    $value = explode(',', $value);
                }
                if ($keyValuePair['is_encrypted'] === 1) {
                    config([$key => cywise_unhash($value)]);
                } else {
                    config([$key => $value]);
                }
            }
            return ['loaded' => true];

        } catch (PDOException $e) {
            printf($e->getMessage());
            return ['loaded' => false];
        }
    }
}
