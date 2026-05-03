<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\FeatureFlags as FeatureFlagsConfig;

final class FeatureFlags
{
    /**
     * @param array<string, bool> $flags
     */
    public function __construct(
        private readonly array $flags,
    ) {
    }

    public static function fromConfig(FeatureFlagsConfig $config): self
    {
        return new self($config->flags);
    }

    public function enabled(string $key): bool
    {
        return $this->flags[$key] ?? false;
    }

    /**
     * @return array<string, bool>
     */
    public function all(): array
    {
        return $this->flags;
    }
}
