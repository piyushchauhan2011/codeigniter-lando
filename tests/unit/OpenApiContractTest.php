<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Lightweight contract guard so declared paths stay aligned with Routes.php.
 *
 * @internal
 */
final class OpenApiContractTest extends TestCase
{
    public function testOpenApiV1DeclaresPublishedJobPaths(): void
    {
        $path = dirname(__DIR__, 2) . '/openapi/openapi-v1.yaml';
        self::assertFileExists($path);

        $yaml = (string) file_get_contents($path);
        self::assertStringContainsString('/api/v1/jobs:', $yaml);
        self::assertStringContainsString('/api/v1/jobs/{id}:', $yaml);
        self::assertStringContainsString('operationId: listPublishedJobs', $yaml);
        self::assertStringContainsString('operationId: showPublishedJob', $yaml);
        self::assertStringContainsString('429', $yaml);
    }
}
