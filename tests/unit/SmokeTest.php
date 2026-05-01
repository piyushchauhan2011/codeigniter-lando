<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testPhpUnitRunsWithExpectedPhpMajor(): void
    {
        self::assertSame(8, PHP_MAJOR_VERSION);
    }
}
