<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class JobAssetModel extends Model
{
    protected $table         = 'job_assets';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'job_id',
        'employer_user_id',
        'bucket',
        'object_key',
        'original_name',
        'mime_type',
        'size_bytes',
        'visibility',
    ];
    protected $useTimestamps = true;

    /**
     * @return list<array<string, mixed>>
     */
    public function findAllForEmployerJob(int $jobId, int $employerUserId): array
    {
        return model(static::class, false)
            ->where('job_id', $jobId)
            ->where('employer_user_id', $employerUserId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function findForEmployer(int $assetId, int $employerUserId): ?array
    {
        $asset = model(static::class, false)
            ->where('id', $assetId)
            ->where('employer_user_id', $employerUserId)
            ->first();

        return $asset !== null ? $asset : null;
    }
}
