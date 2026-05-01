<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class EmployerProfileModel extends Model
{
    protected $table          = 'employer_profiles';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $protectFields  = true;
    protected $allowedFields  = ['id', 'user_id', 'company_name', 'website', 'description', 'logo_path'];
    protected $useTimestamps  = true;
}
