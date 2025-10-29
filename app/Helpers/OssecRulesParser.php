<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OSSEC rule parser.
 *
 * [Application name] [any or all or none] [reference]
 * type:<entry name>;
 *
 * Type can be:
 *  - f (for file)
 *  - p (for process)
 *  - r (for registry)
 *  - d (any file inside the directory)
 *  - c (for command)
 *
 * Additional values:
 *  - For the registry and for directories, use "->" to look for a specific entry and another "->" to look for the value.
 *  - Also, use " -> r:^\. -> ..." to search all files in a directory.
 *  - For files, use "->" to look for a specific value in the file.
 *
 * Values can be preceded by:
 *  - =: (for equal) - default
 *  - r: (for ossec regexes)
 *  - >: (for strcmp greater)
 *  - <: (for strcmp  lower)
 *
 * Multiple patterns can be specified by using " && " between them. All of them must match for it to return true.
 */
class OssecRulesParser
{
    const string PROCESS_RULE = 'process';
    const string REGISTRY_RULE = 'registry';
    const string DIRECTORY_RULE = 'directory';
    const string FILE_RULE = 'file';
    const string COMMAND_RULE = 'command';

    public static function evaluate(array $ctx, array $rule): bool
    {
        /*
         * $ctx = [
         *      'file_exists' => fn(string $file): bool => {...},
         *      'directory_exists' => fn(string $directory): bool => {...},
         *      'registry_entry_exists' => fn(string $entry): bool => {...},
         *      'fetch_file' => fn(string $file): array => {...},
         *      'list_files' => fn(string $directory): array => {...},
         *      'fetch_registry_keys' => fn(string $entry): array => {...},
         *      'fetch_registry_value' => fn(string $entry, string $key): string => {...},
         *      'execute' => fn(string $command): array => {...},
         * ]
         */
        $matchType = $rule['match_type'];
        foreach ($rule['rules'] as $r) {
            $matches = self::match($ctx, $r);
            $isOk = $r['negate'] ? !$matches : $matches;
            if (($matchType === 'all' && !$isOk) || ($matchType === 'none' && $isOk)) {
                return false;
            }
            if ($matchType === 'any' && $isOk) {
                return true;
            }
        }
        return $matchType === 'all' || $matchType === 'none';
    }

    public static function parse(string $text): array
    {
        $rules = [];
        $lines = array_map('trim', explode("\n", $text));
        $vars = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || Str::startsWith($line, "#")) {
                continue;
            }
            $matches = null;
            if (Str::startsWith($line, "$") && Str::endsWith($line, ";")) { // global variables
                $idx = Str::position($line, "=");
                $var = trim(Str::substr($line, 0, $idx));
                $files = array_map('trim', explode(',', Str::substr($line, $idx + 1, Str::length($line) - $idx - 2)));
                $vars[$var] = $files;
            } else if (preg_match('/\[(?<appname>.+)]\s*\[(?<matchtype>any|all|none)]\s*\[(?<references>.*)]/i', $line, $matches)) { // rule description
                $rules[] = [
                    'rule_name' => $matches['appname'],
                    'match_type' => $matches['matchtype'],
                    'references' => $matches['references'] ?
                        collect(explode(',', $matches['references']))
                            ->map(fn(string $ref) => trim($ref))
                            ->filter(fn(string $ref) => !empty($ref))
                            ->toArray() :
                        [],
                    'rules' => [],
                ];
            } else if (preg_match('/^(not\s+)*(?<type>[fpdrc]:)(?<rule>.+);$/i', $line, $matches)) { // rule patterns
                $rule = match ($matches['type']) {
                    'f:' => self::parseFile(trim($matches['rule']), $vars),
                    'p:' => self::parseProcess(trim($matches['rule']), $vars),
                    'r:' => self::parseRegistry(trim($matches['rule']), $vars),
                    'd:' => self::parseDirectory(trim($matches['rule']), $vars),
                    'c:' => self::parseCommand(trim($matches['rule']), $vars),
                    default => null,
                };
                if (!empty($rules) && !empty($rule)) {
                    $rule['negate'] = Str::startsWith($line, "not ");
                    $rules[count($rules) - 1]['rules'][] = $rule;
                }
            }
        }
        if (count($rules) != 1) {
            throw new \Exception("Invalid number of rules: {$text}");
        }
        return $rules[0];
    }

    private static function parseFile(string $rule, array $vars): array
    {
        $matches = null;
        if (preg_match('/(.*)\s*->\s*(.*)/i', $rule, $matches)) {
            return [
                'type' => self::FILE_RULE,
                'files' => $vars[trim($matches[1])] ?? [trim($matches[1])],
                'expr' => array_map('trim', explode(" && ", $matches[2])),
            ];
        }
        if (!Str::contains($rule, "->")) {
            return [
                'type' => self::FILE_RULE,
                'files' => $vars[$rule] ?? [$rule],
                'expr' => null,
            ];
        }
        throw new \Exception("Invalid FILE rule: {$rule}");
    }

    private static function parseDirectory(string $rule, array $vars): array
    {
        $matches = null;
        if (preg_match('/(.*)\s*->\s*(.*)\s*->\s*(.*)/i', $rule, $matches)) {
            return [
                'type' => self::DIRECTORY_RULE,
                'directories' => $vars[trim($matches[1])] ?? [trim($matches[1])],
                'files' => trim($matches[2]),
                'expr' => array_map('trim', explode(" && ", $matches[3])),
            ];
        }
        if (preg_match('/(.*)\s*->\s*(.*)/i', $rule, $matches)) {
            return [
                'type' => self::DIRECTORY_RULE,
                'directories' => $vars[trim($matches[1])] ?? [trim($matches[1])],
                'files' => trim($matches[2]),
                'expr' => null,
            ];
        }
        if (!Str::contains($rule, "->")) {
            return [
                'type' => self::DIRECTORY_RULE,
                'directories' => $vars[$rule] ?? [$rule],
                'files' => null,
                'expr' => null,
            ];
        }
        throw new \Exception("Invalid DIRECTORY rule: {$rule}");
    }

    private static function parseProcess(string $rule, array $vars): array
    {
        throw new \Exception("Invalid PROCESS rule: {$rule}");
    }

    private static function parseRegistry(string $rule, array $vars): array
    {
        $matches = null;
        if (preg_match('/(.*)\s*->\s*(.*)\s*->\s*(.*)/i', $rule, $matches)) {
            return [
                'type' => self::REGISTRY_RULE,
                'entry' => $vars[trim($matches[1])][0] ?? trim($matches[1]),
                'key' => trim($matches[2]),
                'expr' => array_map('trim', explode(" && ", $matches[3])),
            ];
        }
        if (preg_match('/(.*)\s*->\s*(.*)/i', $rule, $matches)) {
            return [
                'type' => self::REGISTRY_RULE,
                'entry' => $vars[trim($matches[1])][0] ?? trim($matches[1]),
                'key' => trim($matches[2]),
                'expr' => null,
            ];
        }
        if (!Str::contains($rule, "->")) {
            return [
                'type' => self::REGISTRY_RULE,
                'entry' => $vars[$rule][0] ?? [$rule],
                'key' => null,
                'expr' => null,
            ];
        }
        throw new \Exception("Invalid REGISTRY rule: {$rule}");
    }

    private static function parseCommand(string $rule, array $vars): array
    {
        $matches = null;
        if (preg_match('/(.*)\s*->\s*(.*)/i', $rule, $matches)) {
            return [
                'type' => self::COMMAND_RULE,
                'cmd' => $vars[trim($matches[1])][0] ?? trim($matches[1]),
                'expr' => array_map('trim', explode(" && ", $matches[2])),
            ];
        }
        if (!Str::contains($rule, "->")) {
            return [
                'type' => self::COMMAND_RULE,
                'cmd' => $vars[$rule][0] ?? $rule,
                'expr' => null,
            ];
        }
        throw new \Exception("Invalid COMMAND rule: {$rule}");
    }

    private static function match(array $ctx, array $rule): bool
    {
        return match ($rule['type']) {

            self::FILE_RULE => collect($rule['files'])
                ->filter(fn(string $file) => $ctx['file_exists']($file))
                ->filter(function (string $file) use ($ctx, $rule) {
                    return !isset($rule['expr']) || collect($ctx['fetch_file']($file))
                            ->filter(fn(string $line) => self::matchExpression($line, $rule['expr']))
                            ->isNotEmpty();
                })
                ->isNotEmpty(),

            self::DIRECTORY_RULE => collect($rule['directories'])
                ->filter(fn(string $directory) => $ctx['directory_exists']($directory))
                ->filter(function (string $directory) use ($ctx, $rule) {
                    return !isset($rule['files']) || collect($ctx['list_files']($directory))
                            ->filter(fn(string $file) => self::matchPattern($file, $rule['files']))
                            ->filter(function (string $file) use ($ctx, $rule) {
                                return !isset($rule['expr']) || collect($ctx['fetch_file']($file))
                                        ->filter(fn(string $line) => self::matchExpression($line, $rule['expr']))
                                        ->isNotEmpty();
                            })
                            ->isNotEmpty();
                })
                ->isNotEmpty(),

            self::REGISTRY_RULE => collect([$rule['entry']])
                ->filter(fn(string $entry) => $ctx['registry_entry_exists']($entry))
                ->filter(function (string $entry) use ($ctx, $rule) {
                    return !isset($rule['key']) || collect($ctx['fetch_registry_keys']($entry))
                            ->filter(fn(string $key) => self::matchPattern($key, $rule['key']))
                            ->filter(function (string $key) use ($ctx, $rule, $entry) {
                                return !isset($rule['expr']) || collect($ctx['fetch_registry_value']($entry, $key))
                                        ->filter(fn(string $value) => self::matchExpression($value, $rule['expr']))
                                        ->isNotEmpty();
                            })
                            ->isNotEmpty();
                })
                ->isNotEmpty(),

            self::COMMAND_RULE => collect([$rule['entry']])
                ->filter(function (string $cmd) use ($ctx, $rule) {
                    return !isset($rule['expr']) || collect($ctx['execute']($cmd))
                            ->filter(fn(string $line) => self::matchExpression($line, $rule['expr']))
                            ->isNotEmpty();
                })
                ->isNotEmpty(),

            default => throw new \Exception("Invalid rule type: {$rule['type']}"),
        };
    }

    private static function matchExpression(string $string, array $expr): bool
    {
        foreach ($expr as $e) {
            if (!self::matchPattern($string, $e)) {
                return false;
            }
        }
        return true;
    }

    private static function matchPattern(string $string, string $pattern): bool
    {
        //Log::debug("Matching {$string} against {$pattern}");

        // Determine if the match must be negated
        $negate = false;
        if (Str::startsWith($pattern, '!')) {
            $negate = true;
            $pattern = Str::substr($pattern, 1);
        }

        // Simple regex match: either it matches or it doesn't!
        if (Str::startsWith($pattern, 'r:')) {
            $pattern = Str::substr($pattern, 2);
            if ($negate) {
                return !preg_match("/{$pattern}/i", $string);
            }
            return (bool)preg_match("/{$pattern}/i", $string);
        }

        // Simple comparisons
        if (Str::startsWith($pattern, '<:')) {
            $pattern = Str::substr($pattern, 2);
            return $negate ? $pattern >= $string : $pattern < $string;
        }
        if (Str::startsWith($pattern, '>:')) {
            $pattern = Str::substr($pattern, 2);
            return $negate ? $pattern <= $string : $pattern > $string;
        }
        if (Str::startsWith($pattern, '=:')) {
            $pattern = Str::substr($pattern, 2);
            return $negate ? $pattern != $string : $pattern == $string;
        }

        // Extract a specific sequence from the input string then compare this sequence against a given value
        $matches = null;
        if (preg_match('/^n:(.*)\s+compare\s+([><=]+)\s*(.*)$/i', $pattern, $matches)) {
            $pattern = trim($matches[1]);
            $operator = trim($matches[2]);
            $value = trim($matches[3]);
            if (!preg_match("/{$pattern}/i", $string, $matches)) {
                $isOk = $negate;
            } else if ($operator === '>') {
                $isOk = $negate ? $matches[1] <= $value : $matches[1] > $value;
            } else if ($operator === '<') {
                $isOk = $negate ? $matches[1] >= $value : $matches[1] < $value;
            } else if ($operator === '=') {
                $isOk = $negate ? $matches[1] != $value : $matches[1] = $value;
            } else if ($operator === '>=') {
                $isOk = $negate ? $matches[1] < $value : $matches[1] >= $value;
            } else if ($operator === '<=') {
                $isOk = $negate ? $matches[1] > $value : $matches[1] <= $value;
            } else if ($operator === '!=' || $operator === '<>') {
                $isOk = $negate ? $matches[1] == $value : $matches[1] != $value;
            } else {
                Log::error("Unknown operation: {$pattern} {$operator} {$value}");
                $isOk = false;
            }
            return $isOk;
        }
        return $negate ? $string != $pattern : $string === $pattern;
    }
}