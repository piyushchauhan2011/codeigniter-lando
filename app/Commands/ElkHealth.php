<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Elastic;
use Config\Services;

class ElkHealth extends BaseCommand
{
    protected $group = 'ELK Lab';

    protected $name = 'elk:health';

    protected $description = 'Check Elasticsearch, Kibana, and APM Server connectivity for the local ELK lab.';

    protected $usage = 'elk:health';

    public function run(array $params): void
    {
        $config = config(Elastic::class);
        $client = Services::elasticClient();

        try {
            $health = $client->clusterHealth();
            CLI::write('Elasticsearch: ' . ($health['status'] ?? 'unknown'), 'green');
            CLI::write('Cluster: ' . ($health['cluster_name'] ?? 'unknown'));
        } catch (\Throwable $exception) {
            CLI::error('Elasticsearch check failed: ' . $exception->getMessage());
        }

        $this->httpCheck('Kibana', $config->kibanaUrl . '/api/status');
        $this->httpCheck('APM Server', $config->apmServerUrl . '/');
    }

    private function httpCheck(string $label, string $url): void
    {
        $headers = @get_headers($url);
        $status  = is_array($headers) ? ($headers[0] ?? 'no status') : 'unreachable';
        $color   = str_contains($status, '200') ? 'green' : 'yellow';

        CLI::write($label . ': ' . $status, $color);
    }
}
