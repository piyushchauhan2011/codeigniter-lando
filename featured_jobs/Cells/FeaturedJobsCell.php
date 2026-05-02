<?php

declare(strict_types=1);

namespace FeaturedJobs\Cells;

use CodeIgniter\View\Cells\Cell;
use Config\Services;
use FeaturedJobs\Models\FeaturedJobModel;

class FeaturedJobsCell extends Cell
{
    public int $limit = 3;

    public function render(): string
    {
        $jobs = model(FeaturedJobModel::class, false)->featured($this->limit);

        return $this->view('featured_jobs', [
            'jobs'        => $jobs,
            'learningUrl' => site_url(Services::portalLocale()->localizePath('learning/modules/featured-jobs')),
        ]);
    }
}
