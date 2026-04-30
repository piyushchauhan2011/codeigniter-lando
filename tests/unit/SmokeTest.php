<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testPhpUnitRunsWithExpectedPhpMajor(): void
    {
        self::assertSame(PHP_MAJOR_VERSION, 8);
    }
}
