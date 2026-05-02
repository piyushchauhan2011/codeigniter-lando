<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\JobPortalDemoSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use DatabaseLab\Models\DatabaseLabModel;

/**
 * @internal
 */
final class DatabaseLabTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;
    protected $refresh   = true;
    protected $seed      = JobPortalDemoSeeder::class;

    public function testDatabaseLabPageRenders(): void
    {
        $result = $this->get('/learning/modules/database-lab');

        $result->assertOK();
        $result->assertSee('Advanced Database Learning Lab');
        $result->assertSee('Indexing deeply');
        $result->assertSee('Transactions and isolation levels');
        $result->assertSee('MySQL vs PostgreSQL differences');
    }

    public function testIndexingAnalysisReturnsStructuredRows(): void
    {
        $analysis = model(DatabaseLabModel::class, false)->indexingAnalysis([]);

        self::assertIsString($analysis['plan']['sql']);
        self::assertStringContainsString('portal_jobs', $analysis['plan']['sql']);
        self::assertNull($analysis['plan']['error']);
        self::assertIsArray($analysis['plan']['columns']);
        self::assertIsArray($analysis['plan']['rows']);
        self::assertNotSame([], $analysis['plan']['rows']);
        self::assertIsArray($analysis['indexes']);
        self::assertIsArray($analysis['suggestedIndexes']);
    }

    public function testShardResolverIsDeterministic(): void
    {
        $model = model(DatabaseLabModel::class, false);

        $first  = $model->shardRoute(123, 456, 'Remote');
        $second = $model->shardRoute(123, 456, 'Remote');

        self::assertSame($first['shard'], $second['shard']);
        self::assertSame('tenant/employer shard key', $first['strategy']);
    }

    public function testLockLabExplainsUnsupportedDrivers(): void
    {
        $lock = model(DatabaseLabModel::class, false)->lockLab();

        self::assertSame('SQLite3', $lock['driver']);
        self::assertFalse($lock['supported']);
        self::assertStringContainsString('prints the SQL', $lock['message']);
        self::assertNotSame([], $lock['lockSql']);
        self::assertArrayHasKey('terminalA', $lock['deadlockSql']);
        self::assertArrayHasKey('terminalB', $lock['deadlockSql']);
    }
}
