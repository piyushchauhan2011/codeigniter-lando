<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DatabaseLab\Models\DatabaseLabModel;

class DbLabTransactionScript extends BaseCommand
{
    protected $group = 'Database Lab';

    protected $name = 'db-lab:transaction-script';

    protected $description = 'Print transaction and isolation-level exercises for the job portal schema.';

    protected $usage = 'db-lab:transaction-script';

    public function run(array $params): void
    {
        $lab = new DatabaseLabModel();

        CLI::write('Driver: ' . db_connect()->DBDriver, 'green');
        CLI::newLine();
        CLI::write('Isolation level snippets:', 'yellow');

        foreach ($lab->isolationLevels() as $level) {
            CLI::write('- ' . $level['name']);
            CLI::write('  MySQL:      ' . $level['mysql']);
            CLI::write('  PostgreSQL: ' . $level['postgres']);
            CLI::write('  Use case:   ' . $level['useCase']);
        }

        CLI::newLine();
        CLI::write('Application transaction exercise:', 'yellow');
        foreach ($lab->transactionExerciseScript() as $line) {
            CLI::write($line);
        }
    }
}
