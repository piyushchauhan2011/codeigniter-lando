<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class TutorialHelloTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $migrate = false;
    protected $seed    = '';

    public function testHelloRouteRendersTutorialView(): void
    {
        $result = $this->get('/hello');

        $result->assertOK();
        $result->assertSee('Hello Route + Controller + View');
        $result->assertSee('Piyush');
    }
}
