<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DatabaseLab\Models\DatabaseLabModel;

class DbLabLockDemo extends BaseCommand
{
    protected $group = 'Database Lab';

    protected $name = 'db-lab:lock-demo';

    protected $description = 'Print a safe row-lock exercise for the job portal schema.';

    protected $usage = 'db-lab:lock-demo';

    public function run(array $params): void
    {
        $lab  = new DatabaseLabModel();
        $lock = $lab->lockLab();

        CLI::write('Driver: ' . $lock['driver'], 'green');
        CLI::write($lock['message']);
        CLI::newLine();
        CLI::write('Run this sequence in one transaction against a disposable row:', 'yellow');

        foreach ($lock['lockSql'] as $line) {
            CLI::write($line);
        }

        CLI::newLine();
        CLI::write('Deadlock avoidance rules:', 'yellow');
        foreach ($lock['rules'] as $rule) {
            CLI::write('- ' . $rule);
        }
    }
}
