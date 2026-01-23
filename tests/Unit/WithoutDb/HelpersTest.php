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

