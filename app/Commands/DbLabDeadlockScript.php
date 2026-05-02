<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DatabaseLab\Models\DatabaseLabModel;

class DbLabDeadlockScript extends BaseCommand
{
    protected $group = 'Database Lab';

    protected $name = 'db-lab:deadlock-script';

    protected $description = 'Print two-terminal deadlock instructions for learning lock ordering.';

    protected $usage = 'db-lab:deadlock-script';

    public function run(array $params): void
    {
        $lab      = new DatabaseLabModel();
        $lock     = $lab->lockLab();
        $deadlock = $lock['deadlockSql'];

        CLI::write('Driver: ' . $lock['driver'], 'green');
        CLI::write($lock['message']);
        CLI::newLine();
        CLI::write('Terminal A:', 'yellow');
        foreach ($deadlock['terminalA'] as $line) {
            CLI::write($line);
        }

        CLI::newLine();
        CLI::write('Terminal B:', 'yellow');
        foreach ($deadlock['terminalB'] as $line) {
            CLI::write($line);
        }

        CLI::newLine();
        CLI::write('The lesson: both terminals lock the same tables in opposite order. Fix the flow by always locking portal_jobs before job_applications.');
    }
}
