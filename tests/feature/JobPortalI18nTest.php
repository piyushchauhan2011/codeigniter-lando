<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Feature;

use App\Database\Seeds\JobPortalDemoSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class JobPortalI18nTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;
    protected $seed    = JobPortalDemoSeeder::class;

    public function testJobsPageShowsEnglishWithoutPrefix(): void
    {
        $result = $this->get('/jobs');

        $result->assertOK();
        $result->assertSee('Browse jobs');
        $result->assertSee('Senior PHP Engineer');
    }

    public function testJobsPageShowsFrenchAtFrPrefix(): void
    {
        $result = $this->get('/fr/jobs');

        $result->assertOK();
        $result->assertSee('Offres');
        $result->assertDontSee('Browse jobs');
    }

    public function testJobsPageShowsEnglishAtEnPrefix(): void
    {
        $result = $this->get('/en/jobs');

        $result->assertOK();
        $result->assertSee('Browse jobs');
        $result->assertSee('Senior PHP Engineer');
    }
}
