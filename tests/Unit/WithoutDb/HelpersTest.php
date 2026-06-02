<?php

test('levenshtein distance of two empty strings is 0', function () {
    expect(cywise_levenshtein_distance('', ''))->toEqual(0);
});

test('levenshtein distance of "QWERTY" and "QWERYT" is 2', function () {
    expect(cywise_levenshtein_distance('QWERTY', 'QWERYT'))->toEqual(2);
    expect(cywise_levenshtein_distance('QWERYT', 'QWERTY'))->toEqual(2);
});

test('levenshtein distance of "kitten" and "sitting" is 3', function () {
    expect(cywise_levenshtein_distance('kitten', 'sitting'))->toEqual(3);
    expect(cywise_levenshtein_distance('sitting', 'kitten'))->toEqual(3);
});

test('levenshtein distance of "saturday" and "sunday" is 3', function () {
    expect(cywise_levenshtein_distance('saturday', 'sunday'))->toEqual(3);
    expect(cywise_levenshtein_distance('sunday', 'saturday'))->toEqual(3);
});

test('levenshtein distance of "sleep" and "fleeting" is 5', function () {
    expect(cywise_levenshtein_distance('sleep', 'fleeting'))->toEqual(5);
    expect(cywise_levenshtein_distance('fleeting', 'sleep'))->toEqual(5);
});

test('levenshtein distance of "ACTION!" and "PL/M" is 7', function () {
    expect(cywise_levenshtein_distance('ACTION!', 'PL/M'))->toEqual(7);
    expect(cywise_levenshtein_distance('PL/M', 'ACTION!'))->toEqual(7);
});

test('levenshtein distance of "rosettacode" and "raisethysword" is 8', function () {
    expect(cywise_levenshtein_distance('rosettacode', 'raisethysword'))->toEqual(8);
    expect(cywise_levenshtein_distance('raisethysword', 'rosettacode'))->toEqual(8);
});

test('levenshtein distance of "Here\'s a bunch of words" and "to wring out this code" is 18', function () {
    expect(cywise_levenshtein_distance('Here\'s a bunch of words', 'to wring out this code'))->toEqual(18);
    expect(cywise_levenshtein_distance('to wring out this code', 'Here\'s a bunch of words'))->toEqual(18);
});

test('levenshtein ratio of two empty strings is 0.0', function () {
    expect(cywise_levenshtein_ratio('', ''))->toEqual(0.0);
});

test('levenshtein ratio of "AB" and "AB" is 0.0', function () {
    expect(cywise_levenshtein_ratio('AB', 'AB'))->toEqual(0.0);
    expect(cywise_levenshtein_ratio('AB', 'AB'))->toEqual(0.0);
});

test('levenshtein ratio of "AB" and "AC" is 0.5', function () {
    expect(cywise_levenshtein_ratio('AB', 'AC'))->toEqual(0.5);
    expect(cywise_levenshtein_ratio('AC', 'AB'))->toEqual(0.5);
});

test('levenshtein ratio of "CD" and "AB" is 1.0', function () {
    expect(cywise_levenshtein_ratio('CD', 'AB'))->toEqual(1.0);
    expect(cywise_levenshtein_ratio('AB', 'CD'))->toEqual(1.0);
});

test('compress log buffer (4l)', function () {
    $buffer = [
        "2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:03:33 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:04:36 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:05:43 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
    ];
    $compressed_buffer = cywise_compress_log_buffer($buffer);
    expect($compressed_buffer)->toEqual([
        "[4x REPEATED LINE] 2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
    ]);
});

test('compress log buffer (4l x 2l)', function () {
    $buffer = [
        "2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:03:33 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:04:36 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:05:43 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:06:17 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "2026-01-14 00:06:56 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
    ];
    $compressed_buffer = cywise_compress_log_buffer($buffer);
    expect($compressed_buffer)->toEqual([
        "[4x REPEATED LINE] 2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "[2x REPEATED LINE] 2026-01-14 00:06:17 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
    ]);
});

test('truncate string shorter than limit', function () {
    $str = 'Short string';
    expect(cywise_truncate_string($str, 50))->toEqual($str);
});

test('truncate string equal to limit', function () {
    $str = str_repeat('a', 100);
    expect(cywise_truncate_string($str, 100))->toEqual($str);
});

test('truncate string longer than limit', function () {

    $start = str_repeat('a', 47);
    $middle = str_repeat('b', 16);
    $end = str_repeat('c', 47);
    $str = $start . $middle . $end;

    // Total length is 110, limit is 100
    // (100 - 5) / 2 = 47.5 -> 47
    $expected = $start . '[...]' . $end;
    expect(cywise_truncate_string($str, 100))->toEqual($expected);
});

test('truncate string with default limit (500)', function () {
    $str = str_repeat('a', 501);
    // (500 - 5) / 2 = 247.5 -> 247
    $expected = str_repeat('a', 247) . '[...]' . str_repeat('a', 247);
    expect(cywise_truncate_string($str))->toEqual($expected);
});

test('truncate string with multibyte characters', function () {

    $start = str_repeat('é', 47);
    $middle = '🚀🚀🚀🚀';
    $end = str_repeat('à', 47);
    $str = $start . $middle . $end;

    // Total mb_length is 47+4+47 = 98. Oh wait, test was 101.
    $str = $start . '🚀🚀🚀🚀🚀🚀🚀' . $end; // 47+7+47 = 101

    // Total mb_length is 101, limit is 100
    // (100 - 5) / 2 = 47
    $expected = $start . '[...]' . $end;
    expect(cywise_truncate_string($str, 100))->toEqual($expected);
});

test('truncate string with very short string but limit smaller than 100', function () {

    $str = "This is a string that is longer than ten characters."; // 52 chars
    $limit = 10;

    // (10 - 5) / 2 = 2
    $start = mb_substr($str, 0, 2);
    $end = mb_substr($str, -2);
    $expected = $start . '[...]' . $end;

    expect(cywise_truncate_string($str, $limit))->toEqual($expected);
});
