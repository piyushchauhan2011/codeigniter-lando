<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\JobApplicationModel;
use App\Models\JobCategoryModel;
use App\Models\JobModel;
use App\Models\SavedJobModel;
use Config\Services;

class Jobs extends BaseController
{
    public function index(): string
    {
        $filters = [
            'q'               => $this->request->getGet('q'),
            'location'        => $this->request->getGet('location'),
            'employment_type' => $this->request->getGet('employment_type'),
            'category_id'     => $this->request->getGet('category_id'),
        ];

        $jobModel = model(JobModel::class, false);
        $jobModel->applyPublishedFilters($filters);
        $jobs  = $jobModel->paginate(10);
        $pager = $jobModel->pager;

        $categories = model(JobCategoryModel::class)->getCachedForForms();

        return view('portal/jobs/index', [
            'title'      => 'Job listings',
            'jobs'       => $jobs,
            'pager'      => $pager,
            'filters'    => $filters,
            'categories' => $categories,
        ]);
    }

    public function show(int $jobId): string
    {
        $jobModel = model(JobModel::class, false);
        $jobModel->applyPublishedFilters([]);
        $job = $jobModel->where('portal_jobs.id', $jobId)->first();

        if ($job === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Job not found.');
        }

        $auth       = Services::portalAuth();
        $applied    = false;
        $saved      = false;
        $canApply   = false;

        if ($auth->check() && $auth->isSeeker()) {
            $applied  = model(JobApplicationModel::class)->hasApplied($jobId, (int) $auth->id());
            $saved    = model(SavedJobModel::class)->isSaved((int) $auth->id(), $jobId);
            $canApply = ! $applied && $job['status'] === 'published';
        }

        $errors = session()->getFlashdata('errors');

        return view('portal/jobs/show', [
            'title'    => $job['title'],
            'job'      => $job,
            'applied'  => $applied,
            'saved'    => $saved,
            'canApply' => $canApply,
            'errors'   => is_array($errors) ? $errors : [],
        ]);
    }
}
