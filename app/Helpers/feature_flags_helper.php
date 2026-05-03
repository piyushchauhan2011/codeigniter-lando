<?php

declare(strict_types=1);

if (! function_exists('feature_enabled')) {
    /**
     * Whether a named feature flag is enabled (see Config\FeatureFlags).
     */
    function feature_enabled(string $key): bool
    {
        return service('featureFlags')->enabled($key);
    }
}
