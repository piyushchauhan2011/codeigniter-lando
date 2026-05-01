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

namespace App\Libraries;

use Config\App;

/**
 * Tracks locale and URL prefix (default English = no prefix, or /en, or /fr) per request.
 */
class PortalLocale
{
    private string $rawPath = '';
    private string $locale  = 'en';

    /**
     * @var ''|'en'|'fr'
     */
    private string $urlPrefix = '';

    public function hydrateFromRequestPath(string $path): void
    {
        $this->rawPath = $path;
        $trimmed       = trim($path, '/');
        $segments      = $trimmed === '' ? [] : explode('/', $trimmed);
        $first         = $segments[0] ?? '';
        $supported     = config(App::class)->supportedLocales;

        if ($first === 'fr' && in_array('fr', $supported, true)) {
            $this->locale    = 'fr';
            $this->urlPrefix = 'fr';

            return;
        }

        if ($first === 'en' && in_array('en', $supported, true)) {
            $this->locale    = 'en';
            $this->urlPrefix = 'en';

            return;
        }

        $this->locale    = 'en';
        $this->urlPrefix = '';
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return ''|'en'|'fr'
     */
    public function getUrlPrefix(): string
    {
        return $this->urlPrefix;
    }

    /**
     * Path segment(s) after optional /fr or /en prefix, no leading slash.
     */
    public function getStrippedPath(): string
    {
        $path = trim($this->rawPath, '/');
        if ($path === '') {
            return '';
        }

        $segments = explode('/', $path);
        $first      = $segments[0];
        if ($first === 'fr' || $first === 'en') {
            array_shift($segments);
        }

        return implode('/', $segments);
    }

    /**
     * Build a path for site_url() using the current URL prefix (keeps user on same language style).
     */
    public function localizePath(string $uri): string
    {
        $uri = trim($uri, '/');
        if ($this->urlPrefix === '') {
            return $uri;
        }

        return $this->urlPrefix . ($uri === '' ? '' : '/' . $uri);
    }

    /**
     * Same portal page under another UI locale (/jobs and /en/... vs default, /fr/...).
     */
    public function siteUrlForLocale(string $targetLocale): string
    {
        $tail = $this->getStrippedPath();
        if ($tail === '') {
            $tail = 'jobs';
        }

        return match ($targetLocale) {
            'fr'    => site_url('fr/' . $tail),
            'en'    => site_url('en/' . $tail),
            default => site_url($tail),
        };
    }
}
