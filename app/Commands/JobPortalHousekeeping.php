<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class JobPortalHousekeeping extends BaseCommand
{
    protected $group       = 'JobPortal';

    protected $name        = 'jobportal:housekeeping';

    protected $description = 'Log published job counts (scheduled housekeeping demo).';

    protected $usage       = 'jobportal:housekeeping';

    public function run(array $params): void
    {
        $db        = db_connect();
        $published = $db->table('portal_jobs')->where('status', 'published')->countAllResults();
        $draft     = $db->table('portal_jobs')->where('status', 'draft')->countAllResults();

        $msg = sprintf(
            '[Job portal housekeeping] published_jobs=%d draft_jobs=%d',
            $published,
            $draft,
        );

        CLI::write($msg);
        log_message('info', $msg);
    }
}
