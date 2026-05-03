<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FeatureFlags;
use CodeIgniter\Test\CIUnitTestCase;
use Config\FeatureFlags as FeatureFlagsConfig;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class FeatureFlagsTest extends CIUnitTestCase
{
    public static function envBoolProvider(): iterable
    {
        yield 'true string' => ['true', true];
        yield '1' => ['1', true];
        yield 'on' => ['on', true];
        yield 'yes' => ['yes', true];
        yield 'false string' => ['false', false];
        yield '0' => ['0', false];
        yield 'off' => ['off', false];
    }

    #[DataProvider('envBoolProvider')]
    public function testParseEnvBool(string $raw, bool $expected): void
    {
        self::assertSame($expected, FeatureFlagsConfig::parseEnvBool($raw));
    }

    public function testEnabledReturnsFalseForUnknownKey(): void
    {
        $flags = new FeatureFlags([
            'elkLabNav'         => true,
            'jobsElasticsearch' => false,
            'jobsApiLiveBanner' => true,
        ]);

        self::assertFalse($flags->enabled('nonexistent'));
    }

    public function testEnabledAndAll(): void
    {
        $map = [
            'elkLabNav'         => false,
            'jobsElasticsearch' => true,
            'jobsApiLiveBanner' => false,
        ];
        $flags = new FeatureFlags($map);

        self::assertFalse($flags->enabled('elkLabNav'));
        self::assertTrue($flags->enabled('jobsElasticsearch'));
        self::assertSame($map, $flags->all());
    }

    public function testFromConfigUsesConfigValues(): void
    {
        $config = new class () extends FeatureFlagsConfig {
            public function __construct()
            {
                parent::__construct();
                $this->flags = [
                    'elkLabNav'         => true,
                    'jobsElasticsearch' => false,
                    'jobsApiLiveBanner' => true,
                ];
            }
        };

        $flags = FeatureFlags::fromConfig($config);

        self::assertTrue($flags->enabled('elkLabNav'));
        self::assertFalse($flags->enabled('jobsElasticsearch'));
        self::assertTrue($flags->enabled('jobsApiLiveBanner'));
    }
}
