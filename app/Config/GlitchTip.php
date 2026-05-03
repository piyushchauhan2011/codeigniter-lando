<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Backend ingest proxy for {@see \App\Controllers\GlitchTipTunnel}.
 *
 * Browser SDK posts envelopes to CodeIgniter (same origin); PHP forwards to GlitchTip on the Docker/Lando network.
 */
class GlitchTip extends BaseConfig
{
    /**
     * GlitchTip HTTP root reachable from appserver (no trailing slash).
     * Lando compose service listens on internal port 8000.
     */
    public string $internalIngestBase = 'http://glitchtip:8000';

    /**
     * If non-empty, envelope DSN path segment must match one of these strings (e.g. project id `"1"`).
     *
     * @var list<string>
     */
    public array $allowedProjectIds = [];

    /**
     * If non-empty, parsed DSN hostname must match one entry (TLS-safe labs).
     *
     * @var list<string>
     */
    public array $allowedDsnHosts = [];

    public function __construct()
    {
        parent::__construct();

        $base = env('glitchtip.internalIngestBase', $this->internalIngestBase);
        $this->internalIngestBase = rtrim((string) $base, '/');

        $ids = env('glitchtip.allowedProjectIds', '');
        if ($ids !== '') {
            $parts                  = array_map(static fn (string $id): string => trim($id), explode(',', (string) $ids));
            $this->allowedProjectIds = array_values(array_filter($parts, static fn ($id): bool => $id !== ''));
        }

        $hosts = env('glitchtip.allowedDsnHosts', '');
        if ($hosts !== '') {
            $parts                   = array_map(static fn (string $h): string => trim($h), explode(',', (string) $hosts));
            $this->allowedDsnHosts = array_values(array_filter($parts, static fn ($h): bool => $h !== ''));
        }
    }
}
