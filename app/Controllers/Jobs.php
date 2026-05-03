<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\JobApplicationModel;
use App\Models\JobCategoryModel;
use App\Models\JobModel;
use App\Models\SavedJobModel;
use Config\Services;
use Throwable;

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

        if (! Services::featureFlags()->enabled('jobsElasticsearch')) {
            $filters['engine'] = 'sql';
        }

        $searchMeta = [
            'engine'       => $filters['engine'] === 'elastic' ? 'elastic' : 'sql',
            'total'        => null,
            'aggregations' => [],
            'error'        => null,
        ];
        $jobs = [];
        $pager = Services::pager();

        if ($filters['engine'] === 'elastic') {
            try {
                $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
                $result = Services::jobSearch()->search($filters, $page, 10);
                $jobs   = $result['hits'];
                $pager  = Services::pager();
                $pager->setPath(Services::portalLocale()->localizePath('jobs'));
                $pager->makeLinks($page, 10, $result['total']);
                $searchMeta['total']        = $result['total'];
                $searchMeta['aggregations'] = $result['aggregations'];
            } catch (Throwable $exception) {
                log_message('error', json_encode([
                    'message' => 'Elasticsearch job search failed, falling back to SQL',
                    'event'   => ['dataset' => 'codeigniter.search'],
                    'error'   => ['type' => $exception::class, 'message' => $exception->getMessage()],
                ], JSON_THROW_ON_ERROR));

                $filters['engine']   = 'sql';
                $searchMeta['engine'] = 'sql';
                $searchMeta['error']  = $exception->getMessage();
            }
        }

        if ($filters['engine'] !== 'elastic') {
            $jobModel = model(JobModel::class, false);
            $jobModel->applyPublishedFilters($filters);
            $jobs  = $jobModel->paginate(10);
            $pager = $jobModel->pager;
            $pager->setPath(Services::portalLocale()->localizePath('jobs'));
        }

        log_message('info', json_encode([
            'message' => 'Job search completed',
            'event'   => ['dataset' => 'codeigniter.search'],
            'labels'  => [
                'engine'          => $searchMeta['engine'],
                'query'           => (string) ($filters['q'] ?? ''),
                'location'        => (string) ($filters['location'] ?? ''),
                'employment_type' => (string) ($filters['employment_type'] ?? ''),
            ],
            'search' => [
                'results_total' => $searchMeta['total'] ?? count($jobs),
            ],
        ], JSON_THROW_ON_ERROR));

        $categories = model(JobCategoryModel::class)->getCachedForForms();

        return view('portal/jobs/index', [
            'title'      => lang('Portal.job_listings_title'),
            'jobs'       => $jobs,
            'pager'      => $pager,
            'filters'    => $filters,
            'categories' => $categories,
            'searchMeta' => $searchMeta,
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
