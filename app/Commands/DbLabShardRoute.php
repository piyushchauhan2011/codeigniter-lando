<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DatabaseLab\Models\DatabaseLabModel;

class DbLabShardRoute extends BaseCommand
{
    protected $group = 'Database Lab';

    protected $name = 'db-lab:shard-route';

    protected $description = 'Resolve a simulated shard for employer, job, or location inputs.';

    protected $usage = 'db-lab:shard-route [--employer=123] [--job=456] [--location=Remote]';

    public function run(array $params): void
    {
        $employer = (int) $this->optionValue('employer', $params, '0');
        $job      = (int) $this->optionValue('job', $params, '0');
        $location = $this->optionValue('location', $params, '');

        $route = (new DatabaseLabModel())->shardRoute($employer, $job, $location);

        CLI::write('Shard: ' . $route['shard'], 'green');
        CLI::write('Strategy: ' . $route['strategy']);
        CLI::write('Input: ' . json_encode($route['input'], JSON_THROW_ON_ERROR));
        CLI::newLine();
        CLI::write('Caveats:', 'yellow');

        foreach ($route['caveats'] as $caveat) {
            CLI::write('- ' . $caveat);
        }
    }

    /**
     * @param array<int|string, string> $params
     */
    private function optionValue(string $name, array $params, string $default): string
    {
        $value = $params[$name] ?? CLI::getOption($name);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $prefix = '--' . $name . '=';
        foreach ($_SERVER['argv'] ?? [] as $argument) {
            if (is_string($argument) && str_starts_with($argument, $prefix)) {
                return substr($argument, strlen($prefix));
            }
        }

        return $default;
    }
}
