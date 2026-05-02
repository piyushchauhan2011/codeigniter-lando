<?php

declare(strict_types=1);

namespace PerformanceLab\Models;

use App\Models\JobCategoryModel;
use App\Models\JobModel;
use CodeIgniter\Model;
use Config\Cache as CacheConfig;
use Throwable;

class PerformanceLabModel extends Model
{
    private const CATEGORY_CACHE_KEY = 'performance_lab_categories_v1';
    private const JOB_LIST_CACHE_PREFIX = 'performance_lab_jobs_';
    private const CATEGORY_CACHE_TTL = 120;
    private const JOB_LIST_CACHE_TTL = 60;

    protected $table         = 'portal_jobs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [];

    /**
     * @return array<string, mixed>
     */
    public function cacheConfigSummary(): array
    {
        $config = config(CacheConfig::class);

        return [
            'handler'       => $config->handler,
            'backupHandler' => $config->backupHandler,
            'prefix'        => $config->prefix,
            'ttl'           => $config->ttl,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryCacheDemo(): array
    {
        $cache  = cache();
        $before = microtime(true);
        $rows   = $cache->get(self::CATEGORY_CACHE_KEY);
        $hit    = is_array($rows);

        if (! $hit) {
            $rows = model(JobCategoryModel::class, false)
                ->orderBy('name', 'ASC')
                ->findAll();

            $cache->save(self::CATEGORY_CACHE_KEY, $rows, self::CATEGORY_CACHE_TTL);
        }

        return [
            'key'       => self::CATEGORY_CACHE_KEY,
            'ttl'       => self::CATEGORY_CACHE_TTL,
            'hit'       => $hit,
            'count'     => count($rows),
            'elapsedMs' => $this->elapsedMs($before),
            'rows'      => array_slice($rows, 0, 5),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function jobListCacheDemo(array $filters): array
    {
        $cache = cache();
        $key   = $this->jobListCacheKey($filters);
        $start = microtime(true);
        $rows  = $cache->get($key);
        $hit   = is_array($rows);

        if (! $hit) {
            $model = model(JobModel::class, false);
            $model->applyPublishedFilters($filters);
            $rows = $model->findAll(5);

            $cache->save($key, $rows, self::JOB_LIST_CACHE_TTL);
        }

        return [
            'key'       => $key,
            'ttl'       => self::JOB_LIST_CACHE_TTL,
            'hit'       => $hit,
            'count'     => count($rows),
            'elapsedMs' => $this->elapsedMs($start),
            'rows'      => $rows,
        ];
    }

    public function clearDemoCache(): void
    {
        cache()->delete(self::CATEGORY_CACHE_KEY);
        cache()->delete($this->jobListCacheKey([]));
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function publicJobListingExplain(array $filters): array
    {
        $model = model(JobModel::class, false);
        $model->applyPublishedFilters($filters);

        $sql = $model->builder()->getCompiledSelect(false);
        $db  = db_connect();

        try {
            $prefix = $db->DBDriver === 'SQLite3' ? 'EXPLAIN QUERY PLAN ' : 'EXPLAIN ';
            $rows   = $db->query($prefix . $sql)->getResultArray();

            return [
                'sql'     => $sql,
                'driver'  => $db->DBDriver,
                'columns' => $rows === [] ? [] : array_keys($rows[0]),
                'rows'    => $rows,
                'error'   => null,
            ];
        } catch (Throwable $exception) {
            return [
                'sql'     => $sql,
                'driver'  => $db->DBDriver,
                'columns' => [],
                'rows'    => [],
                'error'   => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function loadingComparison(): array
    {
        $db           = db_connect();
        $sample       = $db->table('job_applications')
            ->select('seeker_user_id')
            ->orderBy('created_at', 'DESC')
            ->get(1)
            ->getRowArray();
        $seekerUserId = isset($sample['seeker_user_id']) ? (int) $sample['seeker_user_id'] : null;

        if ($seekerUserId === null) {
            return [
                'seekerUserId' => null,
                'lazy'         => $this->emptyLoadingResult(),
                'eager'        => $this->emptyLoadingResult(),
            ];
        }

        return [
            'seekerUserId' => $seekerUserId,
            'lazy'         => $this->lazyApplicationsWithJobs($seekerUserId),
            'eager'        => $this->eagerApplicationsWithJobs($seekerUserId),
        ];
    }

    /**
     * @return list<string>
     */
    public function suggestedIndexes(): array
    {
        return [
            'ALTER TABLE portal_jobs ADD INDEX idx_portal_jobs_public_sort (status, is_featured, created_at);',
            'ALTER TABLE portal_jobs ADD INDEX idx_portal_jobs_public_type_sort (status, employment_type, is_featured, created_at);',
            'ALTER TABLE portal_jobs ADD INDEX idx_portal_jobs_public_category_sort (status, category_id, is_featured, created_at);',
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function jobListCacheKey(array $filters): string
    {
        $normalized = [
            'q'               => trim((string) ($filters['q'] ?? '')),
            'location'        => trim((string) ($filters['location'] ?? '')),
            'employment_type' => trim((string) ($filters['employment_type'] ?? '')),
            'category_id'     => trim((string) ($filters['category_id'] ?? '')),
        ];

        return self::JOB_LIST_CACHE_PREFIX . substr(hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR)), 0, 16);
    }

    /**
     * @return array<string, mixed>
     */
    private function lazyApplicationsWithJobs(int $seekerUserId): array
    {
        $db      = db_connect();
        $start   = microtime(true);
        $queries = 1;
        $rows    = $db->table('job_applications')
            ->where('seeker_user_id', $seekerUserId)
            ->orderBy('created_at', 'DESC')
            ->get(10)
            ->getResultArray();

        foreach ($rows as &$row) {
            $queries++;
            $job = $db->table('portal_jobs')
                ->select('title, location')
                ->where('id', (int) $row['job_id'])
                ->get(1)
                ->getRowArray();

            $row['job_title']    = $job['title'] ?? null;
            $row['job_location'] = $job['location'] ?? null;
        }
        unset($row);

        return [
            'label'     => 'Lazy loading',
            'queries'   => $queries,
            'elapsedMs' => $this->elapsedMs($start),
            'rows'      => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eagerApplicationsWithJobs(int $seekerUserId): array
    {
        $db    = db_connect();
        $start = microtime(true);
        $rows  = $db->table('job_applications')
            ->select('job_applications.*, portal_jobs.title AS job_title, portal_jobs.location AS job_location')
            ->join('portal_jobs', 'portal_jobs.id = job_applications.job_id')
            ->where('job_applications.seeker_user_id', $seekerUserId)
            ->orderBy('job_applications.created_at', 'DESC')
            ->get(10)
            ->getResultArray();

        return [
            'label'     => 'Eager loading',
            'queries'   => 1,
            'elapsedMs' => $this->elapsedMs($start),
            'rows'      => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyLoadingResult(): array
    {
        return [
            'label'     => 'No application rows',
            'queries'   => 0,
            'elapsedMs' => 0.0,
            'rows'      => [],
        ];
    }

    private function elapsedMs(float $start): float
    {
        return round((microtime(true) - $start) * 1000, 2);
    }
}
