<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Cache as CacheConfig;

class CacheSmoke extends BaseCommand
{
    protected $group = 'JobPortal';

    protected $name = 'cache:smoke';

    protected $description = 'Write, read, and delete a small value through the configured CodeIgniter cache handler.';

    protected $usage = 'cache:smoke';

    public function run(array $params): void
    {
        $cache  = service('cache');
        $config = config(CacheConfig::class);
        $key    = 'portal_cache_smoke_' . bin2hex(random_bytes(4));
        $value  = 'CodeIgniter cache smoke at ' . date(DATE_ATOM);

        if (! $cache->save($key, $value, 60)) {
            CLI::error('Cache smoke failed: could not save test value.');

            return;
        }

        $readValue = $cache->get($key);
        $cache->delete($key);

        if ($readValue !== $value) {
            CLI::error('Cache smoke failed: read value did not match written value.');

            return;
        }

        CLI::write('Cache handler: ' . $config->handler, 'green');
        CLI::write('Wrote, read, and deleted key: ' . $key, 'green');
    }
}
