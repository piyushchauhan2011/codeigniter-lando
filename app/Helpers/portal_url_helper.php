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
