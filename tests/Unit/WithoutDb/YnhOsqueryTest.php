<?php

use App\Models\YnhOsquery;

test('compute columns uid on array')
    ->expect(fn () => YnhOsquery::computeColumnsUid(['a', 'b', 'c']))
    ->toEqual('b05555a5d6d4b64fad478a407d21ffd1')
    ->expect(fn () => YnhOsquery::computeColumnsUid(['b', 'a', 'c']))
    ->toEqual('b919231a1aec8102cacdbf55bf397471');

test('compute columns uid on associative array')
    ->expect(fn () => YnhOsquery::computeColumnsUid(['id' => 1, 'array' => ['id' => 1, 'array' => ['a', 'b', 'c']]]))
    ->toEqual('bf1823f8153cfc357a0bfa61e1e2c6a4')
    ->expect(fn () => YnhOsquery::computeColumnsUid(['array' => ['id' => 1, 'array' => ['a', 'b', 'c']], 'id' => 1]))
    ->toEqual('bf1823f8153cfc357a0bfa61e1e2c6a4')
    ->expect(fn () => YnhOsquery::computeColumnsUid(['id' => 1, 'array' => ['id' => 1, 'array' => ['b', 'a', 'c']]]))
    ->toEqual('fb7224001217899d790af7b30f4d8b55')
    ->expect(fn () => YnhOsquery::computeColumnsUid(['id' => 1, 'array' => ['array' => ['b', 'a', 'c'], 'id' => 1]]))
    ->toEqual('fb7224001217899d790af7b30f4d8b55');
