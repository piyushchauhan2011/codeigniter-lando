<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\JobApplicationModel;
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
            'engine'          => $this->request->getGet('engine'),
        ];
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));

        $data = Services::publicJobListing()->buildIndexPageData($filters, $page);

        return view('portal/jobs/index', $data);
    }

    public function show(int $jobId): string
    {
        $job = Services::jobPortalJobRepository()->findPublishedById($jobId);

        if ($job === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Job not found.');
        }

        $auth     = Services::portalAuth();
        $applied  = false;
        $saved    = false;
        $canApply = false;

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
