<?php

declare(strict_types=1);

namespace App\Services\JobPortal;

use App\Libraries\Elastic\JobSearchService;
use App\Libraries\FeatureFlags as FeatureFlagsLib;
use App\Libraries\PortalLocale;
use App\Models\JobCategoryModel;
use App\Repositories\JobPortal\JobRepository;
use CodeIgniter\Pager\Pager;
use Config\Services;
use Throwable;

/**
 * Application service: public job listing (SQL and optional Elasticsearch).
 *
 * Layer map (see plan “beyond MVC”):
 * - Controller {@see \App\Controllers\Jobs}: HTTP — read GET params, call this service, return view.
 * - This service: use case — normalize engine, search/fallback, logging, assemble view data.
 * - {@see JobRepository}: persistence — published job queries via {@see \App\Models\JobModel}.
 * - {@see JobSearchService}: infrastructure — Elasticsearch queries.
 */
class PublicJobListingService
{
    public function __construct(
        private readonly JobSearchService $jobSearch,
        private readonly JobRepository $jobRepository,
        private readonly FeatureFlagsLib $featureFlags,
        private readonly PortalLocale $portalLocale,
    ) {
    }

    /**
     * @param array<string, mixed> $filters Raw GET filters (q, location, employment_type, category_id, engine)
     *
     * @return array{
     *     title: string,
     *     jobs: list<array<string, mixed>>,
     *     pager: Pager,
     *     filters: array<string, mixed>,
     *     categories: list<array<string, mixed>>,
     *     searchMeta: array<string, mixed>
     * }
     */
    public function buildIndexPageData(array $filters, int $page): array
    {
        if (! $this->featureFlags->enabled('jobsElasticsearch')) {
            $filters['engine'] = 'sql';
        }

        $searchMeta = [
            'engine'       => $filters['engine'] === 'elastic' ? 'elastic' : 'sql',
            'total'        => null,
            'aggregations' => [],
            'error'        => null,
        ];
        $jobs  = [];
        $pager = Services::pager();

        if ($filters['engine'] === 'elastic') {
            try {
                $page   = max(1, $page);
                $result = $this->jobSearch->search($filters, $page, 10);
                $jobs   = $result['hits'];
                $pager  = Services::pager();
                $pager->setPath($this->portalLocale->localizePath('jobs'));
                $pager->makeLinks($page, 10, $result['total']);
                $searchMeta['total']        = $result['total'];
                $searchMeta['aggregations'] = $result['aggregations'];
            } catch (Throwable $exception) {
                log_message('error', json_encode([
                    'message' => 'Elasticsearch job search failed, falling back to SQL',
                    'event'   => ['dataset' => 'codeigniter.search'],
                    'error'   => ['type' => $exception::class, 'message' => $exception->getMessage()],
                ], JSON_THROW_ON_ERROR));

                $filters['engine']    = 'sql';
                $searchMeta['engine'] = 'sql';
                $searchMeta['error']  = $exception->getMessage();
            }
        }

        if ($filters['engine'] !== 'elastic') {
            $batch = $this->jobRepository->paginatePublished($filters, 10);
            $jobs  = $batch['jobs'];
            $pager = $batch['pager'];
            $pager->setPath($this->portalLocale->localizePath('jobs'));
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

        return [
            'title'        => lang('Portal.job_listings_title'),
            'jobs'         => $jobs,
            'pager'        => $pager,
            'filters'      => $filters,
            'categories'   => $categories,
            'searchMeta'   => $searchMeta,
        ];
    }
}
