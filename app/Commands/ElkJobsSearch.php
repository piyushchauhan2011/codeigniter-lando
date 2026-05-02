<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class ElkJobsSearch extends BaseCommand
{
    protected $group = 'ELK Lab';

    protected $name = 'elk:jobs:search';

    protected $description = 'Search the Elasticsearch jobs index from the command line.';

    protected $usage = 'elk:jobs:search [--q=php] [--location=remote] [--employment_type=full_time] [--category_id=1]';

    public function run(array $params): void
    {
        $filters = [
            'q'               => $this->optionValue('q', $params),
            'location'        => $this->optionValue('location', $params),
            'employment_type' => $this->optionValue('employment_type', $params),
            'category_id'     => $this->optionValue('category_id', $params),
        ];

        $result = Services::jobSearch()->search($filters);

        CLI::write('Total matches: ' . $result['total'], 'green');
        foreach ($result['hits'] as $hit) {
            CLI::write(sprintf(
                '#%d %s - %s (%s)',
                (int) ($hit['id'] ?? 0),
                (string) ($hit['title'] ?? ''),
                (string) ($hit['company_name'] ?? ''),
                (string) ($hit['location'] ?? ''),
            ));
        }

        CLI::newLine();
        CLI::write('Aggregations:', 'yellow');
        CLI::write(json_encode($result['aggregations'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<int|string, string> $params
     */
    private function optionValue(string $name, array $params): string
    {
        $value = $params[$name] ?? CLI::getOption($name);
        if (is_string($value)) {
            return $value;
        }

        $prefix = '--' . $name . '=';
        foreach ($_SERVER['argv'] ?? [] as $argument) {
            if (is_string($argument) && str_starts_with($argument, $prefix)) {
                return substr($argument, strlen($prefix));
            }
        }

        return '';
    }
}
