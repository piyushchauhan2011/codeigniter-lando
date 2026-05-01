<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class SavedJobModel extends Model
{
    protected $table          = 'saved_jobs';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $protectFields  = true;
    protected $allowedFields  = ['user_id', 'job_id'];
    protected $useTimestamps  = true;
    protected $updatedField   = '';

    public function isSaved(int $userId, int $jobId): bool
    {
        return null !== model(static::class, false)->where('user_id', $userId)->where('job_id', $jobId)->first();
    }

    public function toggle(int $userId, int $jobId): bool
    {
        $m = model(static::class, false);

        if ($this->isSaved($userId, $jobId)) {
            $m->where('user_id', $userId)->where('job_id', $jobId)->delete();

            return false;
        }

        $m->insert([
            'user_id' => $userId,
            'job_id'  => $jobId,
        ]);

        return true;
    }
}
