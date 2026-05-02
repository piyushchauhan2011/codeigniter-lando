<?php

declare(strict_types=1);

namespace FeaturedJobs\Models;

use CodeIgniter\Model;

class FeaturedJobModel extends Model
{
    protected $table         = 'portal_jobs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [];

    /**
     * Return a small promotional list from the existing job portal tables.
     *
     * @return list<array<string, mixed>>
     */
    public function featured(int $limit = 3): array
    {
        return $this->select('portal_jobs.*, employer_profiles.company_name, job_categories.name AS category_name')
            ->join('employer_profiles', 'employer_profiles.user_id = portal_jobs.employer_user_id', 'left')
            ->join('job_categories', 'job_categories.id = portal_jobs.category_id', 'left')
            ->where('portal_jobs.status', 'published')
            ->orderBy('portal_jobs.is_featured', 'DESC')
            ->orderBy('portal_jobs.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
