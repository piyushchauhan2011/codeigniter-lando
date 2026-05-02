<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\JobPortalDemoSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PerformanceLab\Models\PerformanceLabModel;

/**
 * @internal
 */
final class PerformanceLabTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;
    protected $refresh   = true;
    protected $seed      = JobPortalDemoSeeder::class;

    public function testPerformanceLabPageRenders(): void
    {
        $result = $this->get('/learning/modules/performance-lab');

        $result->assertOK();
        $result->assertSee('Performance Learning Lab');
        $result->assertSee('DB query optimization with EXPLAIN');
        $result->assertSee('Lazy loading vs eager loading');
    }

    public function testCategoryCacheDemoMissesThenHits(): void
    {
        $model = model(PerformanceLabModel::class, false);
        $model->clearDemoCache();

        $first  = $model->categoryCacheDemo();
        $second = $model->categoryCacheDemo();

        self::assertFalse($first['hit']);
        self::assertTrue($second['hit']);
        self::assertSame($first['count'], $second['count']);
    }

    public function testPublicJobListingExplainReturnsStructuredRows(): void
    {
        $plan = model(PerformanceLabModel::class, false)->publicJobListingExplain([]);

        self::assertIsString($plan['sql']);
        self::assertStringContainsString('portal_jobs', $plan['sql']);
        self::assertNull($plan['error']);
        self::assertIsArray($plan['columns']);
        self::assertIsArray($plan['rows']);
        self::assertNotSame([], $plan['rows']);
    }
}
