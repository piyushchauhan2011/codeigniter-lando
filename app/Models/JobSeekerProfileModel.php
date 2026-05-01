<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class JobSeekerProfileModel extends Model
{
    protected $table          = 'job_seeker_profiles';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $protectFields  = true;
    protected $allowedFields  = ['user_id', 'headline', 'bio', 'skills', 'resume_path'];
    protected $useTimestamps  = true;
}
