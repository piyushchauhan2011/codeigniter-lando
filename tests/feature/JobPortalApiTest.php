<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class JobPortalApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;

    protected $refresh = true;
    protected $seed    = \App\Database\Seeds\JobPortalDemoSeeder::class;

    public function testJobsApiReturnsJson(): void
    {
        $result = $this->get('/api/jobs');

        $result->assertOK();
        self::assertStringContainsString('application/json', $result->response()->getHeaderLine('Content-Type'));
        $json = json_decode((string) $result->response()->getBody(), true);
        self::assertIsArray($json);
        self::assertNotEmpty($json);
    }
}
