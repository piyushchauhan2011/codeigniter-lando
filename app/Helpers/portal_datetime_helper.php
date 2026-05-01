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

use CodeIgniter\I18n\Time;

if (! function_exists('portal_localized_datetime')) {
    /**
     * Format a DB datetime string for the current request locale.
     */
    function portal_localized_datetime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            $time = Time::parse($value, 'UTC');

            return $time->toLocalizedString('MMM d, yyyy HH:mm');
        } catch (Throwable) {
            return $value;
        }
    }
}

if (! function_exists('portal_employment_label')) {
    /**
     * Localized employment type for display.
     */
    function portal_employment_label(string $raw): string
    {
        $normalized = strtolower(str_replace('-', '_', $raw));
        $langKey    = 'employment_' . $normalized;
        $line       = lang('Portal.' . $langKey);

        return $line !== 'Portal.' . $langKey ? $line : str_replace('_', '-', $raw);
    }
}
