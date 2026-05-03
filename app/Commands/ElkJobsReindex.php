<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class ElkJobsReindex extends BaseCommand
{
    protected $group = 'ELK Lab';

    protected $name = 'elk:jobs:reindex';

    protected $description = 'Recreate the Elasticsearch jobs index and index published portal jobs.';

    protected $usage = 'elk:jobs:reindex';

    public function run(array $params): void
    {
        $search = Services::jobSearch();

        $search->createIndex(true);
        $count = $search->reindexPublishedJobs();

        CLI::write('Reindexed published jobs: ' . $count, 'green');
    }
}
