<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class JobModel extends Model
{
    protected $table          = 'portal_jobs';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $protectFields  = true;
    protected $allowedFields  = [
        'employer_user_id',
        'category_id',
        'title',
        'description',
        'employment_type',
        'location',
        'salary_min',
        'salary_max',
        'status',
        'is_featured',
        'featured_until',
    ];
    protected $useTimestamps = true;

    /**
     * Paginated listing for public search (bookmarkable GET filters).
     *
     * @param array<string, mixed> $filters Keys: q, location, employment_type, category_id
     *
     * @return list<array<string, mixed>>
     */
    public function paginatePublished(array $filters, int $perPage = 10): array
    {
        $this->applyPublishedFilters($filters);

        return $this->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function applyPublishedFilters(array $filters): self
    {
        $this->select('portal_jobs.*, employer_profiles.company_name')
            ->join('employer_profiles', 'employer_profiles.user_id = portal_jobs.employer_user_id', 'left')
            ->where('portal_jobs.status', 'published');

        $term = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($term !== '') {
            $this->groupStart()
                ->like('portal_jobs.title', $term)
                ->orLike('portal_jobs.description', $term)
                ->groupEnd();
        }

        $loc = isset($filters['location']) ? trim((string) $filters['location']) : '';
        if ($loc !== '') {
            $this->like('portal_jobs.location', $loc);
        }

        $etype = isset($filters['employment_type']) ? trim((string) $filters['employment_type']) : '';
        if ($etype !== '') {
            $this->where('portal_jobs.employment_type', $etype);
        }

        $catId = $filters['category_id'] ?? '';
        if ($catId !== '' && ctype_digit((string) $catId)) {
            $this->where('portal_jobs.category_id', (int) $catId);
        }

        $this->orderBy('portal_jobs.is_featured', 'DESC')
            ->orderBy('portal_jobs.created_at', 'DESC');

        return $this;
    }

    public function belongsToEmployer(int $jobId, int $employerUserId): bool
    {
        $m = model(static::class, false);

        return null !== $m->where('id', $jobId)->where('employer_user_id', $employerUserId)->first();
    }
}
