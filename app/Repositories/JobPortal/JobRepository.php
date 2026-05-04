<?php

declare(strict_types=1);

namespace App\Repositories\JobPortal;

use App\Models\JobModel;
use CodeIgniter\Pager\Pager;

/**
 * Persistence for portal_jobs. Wraps {@see JobModel} so controllers/services
 * do not chain query builders (repository role) while keeping CI4 Model (DB detail).
 */
class JobRepository
{
    /**
     * @param array<string, mixed> $filters Keys: q, location, employment_type, category_id
     *
     * @return array{jobs: list<array<string, mixed>>, pager: Pager}
     */
    public function paginatePublished(array $filters, int $perPage = 10): array
    {
        $jobModel = model(JobModel::class, false);
        $jobModel->applyPublishedFilters($filters);
        $jobs = $jobModel->paginate($perPage);

        return [
            'jobs'  => $jobs,
            'pager' => $jobModel->pager,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublishedById(int $jobId): ?array
    {
        $jobModel = model(JobModel::class, false);
        $jobModel->applyPublishedFilters([]);

        $row = $jobModel->where('portal_jobs.id', $jobId)->first();

        return $row !== null ? $row : null;
    }

    /**
     * All jobs for an employer dashboard (any status), newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function findAllByEmployerUserIdNewestFirst(int $employerUserId): array
    {
        return model(JobModel::class, false)
            ->where('employer_user_id', $employerUserId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
