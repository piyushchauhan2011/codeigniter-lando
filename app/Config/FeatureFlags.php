<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class FeatureFlags extends BaseConfig
{
    /**
     * Known flags and their defaults (overridable via .env as flags.{key}).
     *
     * @var array<string, bool>
     */
    public array $flags = [
        'elkLabNav'          => true,
        'jobsElasticsearch'  => true,
        'jobsApiLiveBanner'  => true,
    ];

    public function __construct()
    {
        parent::__construct();

        foreach (array_keys($this->flags) as $key) {
            $raw = env('flags.' . $key);
            if ($raw === null || $raw === '') {
                continue;
            }
            $this->flags[$key] = self::parseEnvBool($raw);
        }
    }

    public static function parseEnvBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
