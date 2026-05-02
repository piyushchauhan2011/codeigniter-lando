<?php

declare(strict_types=1);

namespace FeaturedJobs\Controllers;

use App\Controllers\BaseController;
use FeaturedJobs\Models\FeaturedJobModel;

class FeaturedJobs extends BaseController
{
    public function index(): string
    {
        $jobs = model(FeaturedJobModel::class, false)->featured(5);

        return view('FeaturedJobs\Views\index', [
            'title' => lang('FeaturedJobs.page_title'),
            'jobs'  => $jobs,
        ]);
    }
}
