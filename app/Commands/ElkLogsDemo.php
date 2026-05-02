<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ElkLogsDemo extends BaseCommand
{
    protected $group = 'ELK Lab';

    protected $name = 'elk:logs:demo';

    protected $description = 'Write sample structured logs for Kibana Discover exercises.';

    protected $usage = 'elk:logs:demo';

    public function run(array $params): void
    {
        foreach (['info', 'warning', 'error'] as $level) {
            log_message($level, json_encode([
                'message' => 'ELK CLI demo ' . $level . ' log',
                'event'   => ['dataset' => 'codeigniter.elk_cli'],
                'labels'  => [
                    'demo_kind' => 'cli_log',
                    'sample'    => $level,
                ],
            ], JSON_THROW_ON_ERROR));
        }

        CLI::write('Wrote info, warning, and error JSON log events.', 'green');
    }
}
