<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\JobApplicationModel;
use App\Models\JobModel;
use CodeIgniter\Shield\Models\UserModel;

class Admin extends BaseController
{
    public function dashboard(): string
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class, false);
        $users     = $userModel
            ->withIdentities()
            ->withGroups()
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return view('portal/admin/dashboard', [
            'title'            => 'Admin dashboard',
            'users'            => $users,
            'totalUsers'       => count($users),
            'publishedJobs'    => model(JobModel::class, false)->where('status', 'published')->countAllResults(),
            'totalApplications' => model(JobApplicationModel::class, false)->countAllResults(),
        ]);
    }
}
