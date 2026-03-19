<?php

use App\Listeners\IngestFileListener;

test('ingest file listener truncates oversized chunk text', function () {
    $listener = new IngestFileListener();
    $method = new \ReflectionMethod(IngestFileListener::class, 'truncateChunkText');
    $method->setAccessible(true);

    $text = str_repeat('a', 6000);
    $truncated = $method->invoke($listener, $text, 123, 7);

    expect(strlen($truncated))->toBe(4999)
        ->and($truncated)->toBe(str_repeat('a', 4999));
});

test('ingest file listener keeps chunk text unchanged when short enough', function () {
    $listener = new IngestFileListener();
    $method = new \ReflectionMethod(IngestFileListener::class, 'truncateChunkText');
    $method->setAccessible(true);

    $text = 'bonjour';
    $truncated = $method->invoke($listener, $text, 123, 7);

    expect($truncated)->toBe($text);
});
