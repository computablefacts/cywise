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

test('compress log buffer (2b x 2l x 2b)', function () {
    $buffer = [
        "2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:03:17 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "2026-01-14 00:03:33 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:04:18 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "2026-01-14 00:04:20 - sentinel-api (ip address: 127.0.0.1) - Busybox is installed, it is often used by attacker to create reverse shell (shell access that connect to a remote server) or listen on the network to provide a shell access or exfiltration means. (criticality: 20)",
        "2026-01-14 00:04:25 - sentinel-api (ip address: 127.0.0.1) - Busybox is installed, it is often used by attacker to create reverse shell (shell access that connect to a remote server) or listen on the network to provide a shell access or exfiltration means. (criticality: 20)",
        "2026-01-14 00:04:36 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:05:28 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "2026-01-14 00:05:43 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:06:47 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)"
    ];
    $compressed_buffer = cywise_compress_log_buffer($buffer);
    expect($compressed_buffer)->toEqual([
        "[BEGIN 2x REPEATED BLOCK]",
        "2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:03:17 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "[END 2x REPEATED BLOCK]",
        "[BEGIN 2x REPEATED LINE]",
        "2026-01-14 00:04:20 - sentinel-api (ip address: 127.0.0.1) - Busybox is installed, it is often used by attacker to create reverse shell (shell access that connect to a remote server) or listen on the network to provide a shell access or exfiltration means. (criticality: 20)",
        "[END 2x REPEATED LINE]",
        "[BEGIN 2x REPEATED BLOCK]",
        "2026-01-14 00:04:36 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:05:28 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "[END 2x REPEATED BLOCK]"
    ]);
});

test('compress log buffer (2b x 1 x 2b)', function () {
    $buffer = [
        "2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:03:17 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "2026-01-14 00:03:33 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:04:18 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "2026-01-14 00:04:20 - sentinel-api (ip address: 127.0.0.1) - Busybox is installed, it is often used by attacker to create reverse shell (shell access that connect to a remote server) or listen on the network to provide a shell access or exfiltration means. (criticality: 20)",
        "2026-01-14 00:04:36 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:05:28 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "2026-01-14 00:05:43 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:06:47 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)"
    ];
    $compressed_buffer = cywise_compress_log_buffer($buffer);
    expect($compressed_buffer)->toEqual([
        "[BEGIN 2x REPEATED BLOCK]",
        "2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:03:17 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "[END 2x REPEATED BLOCK]",
        "2026-01-14 00:04:20 - sentinel-api (ip address: 127.0.0.1) - Busybox is installed, it is often used by attacker to create reverse shell (shell access that connect to a remote server) or listen on the network to provide a shell access or exfiltration means. (criticality: 20)",
        "[BEGIN 2x REPEATED BLOCK]",
        "2026-01-14 00:04:36 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:05:28 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "[END 2x REPEATED BLOCK]"
    ]);
});

test('compress log buffer (4b)', function () {
    $buffer = [
        "2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:03:17 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "2026-01-14 00:03:33 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:04:18 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "2026-01-14 00:04:36 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:05:28 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "2026-01-14 00:05:43 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:06:47 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)"
    ];
    $compressed_buffer = cywise_compress_log_buffer($buffer);
    expect($compressed_buffer)->toEqual([
        "[BEGIN 4x REPEATED BLOCK]",
        "2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "2026-01-14 00:03:17 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "[END 4x REPEATED BLOCK]"
    ]);
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
        "[BEGIN 4x REPEATED LINE]",
        "2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "[END 4x REPEATED LINE]",
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
        "[BEGIN 4x REPEATED LINE]",
        "2026-01-14 00:02:18 - sentinel-api (ip address: 127.0.0.1) - Nmap was used on the machine, this tool is often used by attackers to scan network. (criticality: 30)",
        "[END 4x REPEATED LINE]",
        "[BEGIN 2x REPEATED LINE]",
        "2026-01-14 00:06:17 - sentinel-api (ip address: 127.0.0.1) - Nmap detected scanning the network, commonly used for reconnaissance and enumeration. (criticality: 50)",
        "[END 2x REPEATED LINE]",
    ]);
});
