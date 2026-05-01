<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

/**
 * Ensures PHPUnit migrations run against the tests DB group when ENVIRONMENT is testing.
 *
 * @see \CodeIgniter\Database\MigrationRunner::migrate()
 */
abstract class AppMigration extends Migration
{
    public function __construct(?Forge $forge = null)
    {
        if (ENVIRONMENT === 'testing') {
            $this->DBGroup = 'tests';
        }

        parent::__construct($forge);
    }
}
