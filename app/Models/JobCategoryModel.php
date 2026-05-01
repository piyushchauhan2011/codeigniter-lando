<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class JobCategoryModel extends Model
{
    protected $table          = 'job_categories';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $protectFields  = true;
    protected $allowedFields  = ['slug', 'name'];
    protected $useTimestamps  = true;

    /**
     * Cached category list for filters / forms (invalidate when categories change).
     *
     * @return list<array<string, mixed>>
     */
    public function getCachedForForms(): array
    {
        return cache()->remember('portal_job_categories_v1', 3600, fn (): array => $this->orderBy('name', 'ASC')->findAll());
    }
}
