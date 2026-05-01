<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class JobPortalBrowseTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    /** @see CIUnitTestCase defaults to Tests\Support — App migrations live under App namespace */
    protected $namespace = null;

    protected $refresh = true;
    protected $seed    = \App\Database\Seeds\JobPortalDemoSeeder::class;

    public function testPublicJobListShowsDemoRole(): void
    {
        $result = $this->get('/jobs');

        $result->assertOK();
        $result->assertSee('Senior PHP Engineer');
    }
}
