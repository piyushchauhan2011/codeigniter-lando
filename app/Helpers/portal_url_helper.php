<?php

declare(strict_types=1);

use Config\Services;

if (! function_exists('portal_url')) {
    /**
     * Portal URL using the current /fr or /en prefix (default English has no prefix).
     */
    function portal_url(string $uri = ''): string
    {
        $uri = trim($uri, '/');

        return site_url(Services::portalLocale()->localizePath($uri));
    }
}
