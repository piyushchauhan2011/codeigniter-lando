<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class PaymentIntentModel extends Model
{
    protected $table         = 'portal_payment_intents';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'job_id',
        'employer_user_id',
        'idempotency_key',
        'provider_reference',
        'amount_cents',
        'currency',
        'scenario',
        'status',
        'attempts_count',
        'last_error',
        'paid_at',
        'dead_lettered_at',
    ];
    protected $useTimestamps = true;

    public function findByIdempotencyKey(string $key): ?array
    {
        $row = model(static::class, false)->where('idempotency_key', $key)->first();

        return $row !== null ? $row : null;
    }

    public function findWithJobForEmployer(int $intentId, int $employerUserId): ?array
    {
        $row = model(static::class, false)
            ->select('portal_payment_intents.*, portal_jobs.title AS job_title, portal_jobs.status AS job_status, portal_jobs.is_featured, portal_jobs.featured_until')
            ->join('portal_jobs', 'portal_jobs.id = portal_payment_intents.job_id')
            ->where('portal_payment_intents.id', $intentId)
            ->where('portal_payment_intents.employer_user_id', $employerUserId)
            ->first();

        return $row !== null ? $row : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latestByEmployerIndexedByJob(int $employerUserId): array
    {
        $rows = model(static::class, false)
            ->where('employer_user_id', $employerUserId)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $indexed = [];
        foreach ($rows as $row) {
            $jobId = (int) $row['job_id'];
            if (! isset($indexed[$jobId])) {
                $indexed[$jobId] = $row;
            }
        }

        return $indexed;
    }
}
