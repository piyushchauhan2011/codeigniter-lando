<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class JobApplicationModel extends Model
{
    protected $table          = 'job_applications';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $protectFields  = true;
    protected $allowedFields  = ['job_id', 'seeker_user_id', 'cover_letter', 'resume_path', 'status'];
    protected $useTimestamps  = true;

    public function hasApplied(int $jobId, int $seekerUserId): bool
    {
        return null !== model(static::class, false)->where('job_id', $jobId)->where('seeker_user_id', $seekerUserId)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findAllForSeeker(int $seekerUserId): array
    {
        return model(static::class, false)
            ->select('job_applications.*, portal_jobs.title AS job_title, portal_jobs.location')
            ->join('portal_jobs', 'portal_jobs.id = job_applications.job_id')
            ->where('job_applications.seeker_user_id', $seekerUserId)
            ->orderBy('job_applications.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Applications for jobs owned by employer (joined rows).
     *
     * @return list<array<string, mixed>>
     */
    public function findForEmployerJob(int $jobId, int $employerUserId): array
    {
        return model(static::class, false)
            ->select(
                'job_applications.*, portal_jobs.title AS job_title, job_seeker_profiles.headline AS seeker_headline, portal_users.email AS seeker_email',
            )
            ->join('portal_jobs', 'portal_jobs.id = job_applications.job_id')
            ->join('portal_users', 'portal_users.id = job_applications.seeker_user_id')
            ->join('job_seeker_profiles', 'job_seeker_profiles.user_id = job_applications.seeker_user_id', 'left')
            ->where('job_applications.job_id', $jobId)
            ->where('portal_jobs.employer_user_id', $employerUserId)
            ->orderBy('job_applications.created_at', 'DESC')
            ->findAll();
    }

    public function findWithJobForSeeker(int $applicationId, int $seekerUserId): ?array
    {
        $row = model(static::class, false)
            ->select('job_applications.*, portal_jobs.title AS job_title')
            ->join('portal_jobs', 'portal_jobs.id = job_applications.job_id')
            ->where('job_applications.id', $applicationId)
            ->where('job_applications.seeker_user_id', $seekerUserId)
            ->first();

        return $row ?: null;
    }
}
