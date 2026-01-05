<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function test_truth(): void
    {
        $this->assertTrue(true);
    }
}
