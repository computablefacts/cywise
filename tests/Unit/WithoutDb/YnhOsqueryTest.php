<?php

use App\Models\YnhOsquery;

test('compute columns uid on array', function () {
    $uid = YnhOsquery::computeColumnsUid(["a", "b", "c"]);
    expect($uid)->toEqual("b05555a5d6d4b64fad478a407d21ffd1");

    $uid = YnhOsquery::computeColumnsUid(["b", "a", "c"]);
    expect($uid)->toEqual("b919231a1aec8102cacdbf55bf397471");
});

test('compute columns uid on associative array', function () {
    $uid = YnhOsquery::computeColumnsUid(["id" => 1, "array" => ["id" => 1, "array" => ["a", "b", "c"]]]);
    expect($uid)->toEqual("bf1823f8153cfc357a0bfa61e1e2c6a4");

    $uid = YnhOsquery::computeColumnsUid(["array" => ["id" => 1, "array" => ["a", "b", "c"]], "id" => 1]);
    expect($uid)->toEqual("bf1823f8153cfc357a0bfa61e1e2c6a4");

    $uid = YnhOsquery::computeColumnsUid(["id" => 1, "array" => ["id" => 1, "array" => ["b", "a", "c"]]]);
    expect($uid)->toEqual("fb7224001217899d790af7b30f4d8b55");

    $uid = YnhOsquery::computeColumnsUid(["id" => 1, "array" => ["array" => ["b", "a", "c"], "id" => 1]]);
    expect($uid)->toEqual("fb7224001217899d790af7b30f4d8b55");
});
