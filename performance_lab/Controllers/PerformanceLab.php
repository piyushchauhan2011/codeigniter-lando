<?php

declare(strict_types=1);

namespace PerformanceLab\Controllers;

use App\Controllers\BaseController;
use Config\Services;
use PerformanceLab\Models\PerformanceLabModel;

class PerformanceLab extends BaseController
{
    public function index(): string
    {
        $model = new PerformanceLabModel();

        $filters = [
            'q'               => $this->request->getGet('q'),
            'location'        => $this->request->getGet('location'),
            'employment_type' => $this->request->getGet('employment_type'),
            'category_id'     => $this->request->getGet('category_id'),
        ];

        $categoryCache = $model->categoryCacheDemo();
        $jobListCache  = $model->jobListCacheDemo($filters);

        return view('PerformanceLab\Views\index', [
            'title'                => lang('PerformanceLab.page_title'),
            'cacheConfig'          => $model->cacheConfigSummary(),
            'categoryCache'        => $categoryCache,
            'jobListCache'         => $jobListCache,
            'queryPlan'            => $model->publicJobListingExplain($filters),
            'loadingComparison'    => $model->loadingComparison(),
            'suggestedIndexes'     => $model->suggestedIndexes(),
            'cachedPageUrl'        => site_url(Services::portalLocale()->localizePath('learning/modules/performance-lab/page-cache/cached')),
            'uncachedPageUrl'      => site_url(Services::portalLocale()->localizePath('learning/modules/performance-lab/page-cache/uncached')),
            'clearCacheAction'     => site_url(Services::portalLocale()->localizePath('learning/modules/performance-lab/cache/clear')),
            'jobShowCacheWarning'  => lang('PerformanceLab.job_show_cache_warning'),
        ]);
    }

    public function clearCache()
    {
        (new PerformanceLabModel())->clearDemoCache();

        return redirect()
            ->to(site_url(Services::portalLocale()->localizePath('learning/modules/performance-lab')))
            ->with('message', lang('PerformanceLab.cache_cleared'));
    }

    public function cachedPage(): string
    {
        service('responsecache')->setTtl(60);

        return view('PerformanceLab\Views\page_cache_demo', [
            'title'       => lang('PerformanceLab.cached_page_title'),
            'heading'     => lang('PerformanceLab.cached_page_title'),
            'description' => lang('PerformanceLab.cached_page_intro'),
            'generatedAt' => date(DATE_ATOM),
            'backUrl'     => site_url(Services::portalLocale()->localizePath('learning/modules/performance-lab')),
        ]);
    }

    public function uncachedPage(): string
    {
        return view('PerformanceLab\Views\page_cache_demo', [
            'title'       => lang('PerformanceLab.uncached_page_title'),
            'heading'     => lang('PerformanceLab.uncached_page_title'),
            'description' => lang('PerformanceLab.uncached_page_intro'),
            'generatedAt' => date(DATE_ATOM),
            'backUrl'     => site_url(Services::portalLocale()->localizePath('learning/modules/performance-lab')),
        ]);
    }
}
