<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EmployerProfileModel;
use App\Models\JobApplicationModel;
use App\Models\JobCategoryModel;
use App\Models\JobModel;
use App\Models\PaymentIntentModel;
use Config\Services;

class Employer extends BaseController
{
    public function dashboard(): string
    {
        $auth = Services::portalAuth();

        $jobs = model(JobModel::class, false)
            ->where('employer_user_id', $auth->id())
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return view('portal/employer/dashboard', [
            'title'          => 'Employer dashboard',
            'jobs'           => $jobs,
            'paymentIntents' => model(PaymentIntentModel::class)->latestByEmployerIndexedByJob((int) $auth->id()),
        ]);
    }

    public function profile(): string
    {
        $auth    = Services::portalAuth();
        $profile = model(EmployerProfileModel::class, false)->where('user_id', $auth->id())->first();

        if ($profile === null) {
            throw new \RuntimeException('Employer profile missing.');
        }

        return view('portal/employer/profile', [
            'title'   => 'Company profile',
            'profile' => $profile,
            'errors'  => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function updateProfile()
    {
        if (! $this->validate('portal_employer_profile')) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $auth    = Services::portalAuth();
        $profile = model(EmployerProfileModel::class, false)->where('user_id', $auth->id())->first();

        if ($profile === null) {
            return redirect()->back()->with('error', 'Profile not found.');
        }

        $data = [
            'company_name' => (string) $this->request->getPost('company_name'),
            'website'      => (string) $this->request->getPost('website'),
            'description'  => (string) $this->request->getPost('description'),
        ];

        $file = $this->request->getFile('logo');
        if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
            if (! $this->validate([
                'logo' => 'uploaded[logo]|max_size[logo,2048]|ext_in[logo,png,jpg,jpeg,webp]',
            ])) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $target = FCPATH . 'uploads/job_portal/logos/';
            if (! is_dir($target) && ! mkdir($target, 0775, true) && ! is_dir($target)) {
                return redirect()->back()->with('error', 'Cannot create upload directory.');
            }

            $newName = $file->getRandomName();
            $file->move($target, $newName);
            $data['logo_path'] = 'job_portal/logos/' . $newName;
        }

        model(EmployerProfileModel::class, false)->update($profile['id'], $data);

        return redirect()->to(site_url(Services::portalLocale()->localizePath('employer/profile')))->with('message', 'Profile updated.');
    }

    public function newJob(): string
    {
        $categories = model(JobCategoryModel::class)->getCachedForForms();

        return view('portal/employer/job_form', [
            'title'      => 'Post a job',
            'job'        => null,
            'categories' => $categories,
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function createJob()
    {
        if (! $this->validate('portal_job_form')) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $min = $this->request->getPost('salary_min');
        $max = $this->request->getPost('salary_max');
        if ($min !== null && $min !== '' && $max !== null && $max !== '' && (int) $max < (int) $min) {
            return redirect()->back()->withInput()->with('errors', ['salary_max' => 'Maximum salary must be greater than or equal to minimum.']);
        }

        $auth = Services::portalAuth();

        $categoryId = $this->request->getPost('category_id');
        $insert     = [
            'employer_user_id' => $auth->id(),
            'category_id'      => ($categoryId !== null && $categoryId !== '' && ctype_digit((string) $categoryId))
                ? (int) $categoryId : null,
            'title'            => (string) $this->request->getPost('title'),
            'description'      => (string) $this->request->getPost('description'),
            'employment_type'  => (string) $this->request->getPost('employment_type'),
            'location'         => (string) $this->request->getPost('location'),
            'salary_min'       => ($min !== null && $min !== '') ? (int) $min : null,
            'salary_max'       => ($max !== null && $max !== '') ? (int) $max : null,
            'status'           => (string) $this->request->getPost('status'),
        ];

        $jobId = model(JobModel::class, false)->insert($insert, true);

        return redirect()->to(site_url(Services::portalLocale()->localizePath('employer')))->with('message', 'Job saved (ID ' . $jobId . ').');
    }

    public function editJob(int $jobId): string
    {
        $auth = Services::portalAuth();
        $job  = model(JobModel::class, false)->find($jobId);

        if ($job === null || (int) $job['employer_user_id'] !== $auth->id()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $categories = model(JobCategoryModel::class)->getCachedForForms();

        return view('portal/employer/job_form', [
            'title'      => 'Edit job',
            'job'        => $job,
            'categories' => $categories,
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function updateJob(int $jobId)
    {
        $auth = Services::portalAuth();
        $job  = model(JobModel::class, false)->find($jobId);

        if ($job === null || (int) $job['employer_user_id'] !== $auth->id()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->validate('portal_job_form')) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $min = $this->request->getPost('salary_min');
        $max = $this->request->getPost('salary_max');
        if ($min !== null && $min !== '' && $max !== null && $max !== '' && (int) $max < (int) $min) {
            return redirect()->back()->withInput()->with('errors', ['salary_max' => 'Maximum salary must be greater than or equal to minimum.']);
        }

        $categoryId = $this->request->getPost('category_id');
        $data       = [
            'category_id'     => ($categoryId !== null && $categoryId !== '' && ctype_digit((string) $categoryId))
                ? (int) $categoryId : null,
            'title'           => (string) $this->request->getPost('title'),
            'description'     => (string) $this->request->getPost('description'),
            'employment_type' => (string) $this->request->getPost('employment_type'),
            'location'        => (string) $this->request->getPost('location'),
            'salary_min'      => ($min !== null && $min !== '') ? (int) $min : null,
            'salary_max'      => ($max !== null && $max !== '') ? (int) $max : null,
            'status'          => (string) $this->request->getPost('status'),
        ];

        model(JobModel::class, false)->update($jobId, $data);

        return redirect()->to(site_url(Services::portalLocale()->localizePath('employer')))->with('message', 'Job updated.');
    }

    public function deleteJob(int $jobId)
    {
        $auth = Services::portalAuth();
        $job  = model(JobModel::class, false)->find($jobId);

        if ($job === null || (int) $job['employer_user_id'] !== $auth->id()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        model(JobModel::class, false)->delete($jobId);

        return redirect()->to(site_url(Services::portalLocale()->localizePath('employer')))->with('message', 'Job deleted.');
    }

    public function applications(int $jobId): string
    {
        $auth = Services::portalAuth();

        if (! model(JobModel::class)->belongsToEmployer($jobId, (int) $auth->id())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $rows = model(JobApplicationModel::class)->findForEmployerJob($jobId, (int) $auth->id());

        return view('portal/employer/applications', [
            'title'        => 'Applicants',
            'job_id'       => $jobId,
            'applications' => $rows,
        ]);
    }

    public function updateApplicationStatus(int $applicationId)
    {
        $auth = Services::portalAuth();
        $app  = model(JobApplicationModel::class, false)->find($applicationId);

        if ($app === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! model(JobModel::class)->belongsToEmployer((int) $app['job_id'], (int) $auth->id())) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

        $status = (string) $this->request->getPost('status');
        if (! in_array($status, ['submitted', 'shortlisted', 'rejected'], true)) {
            return redirect()->back()->with('error', 'Invalid status.');
        }

        model(JobApplicationModel::class, false)->update($applicationId, ['status' => $status]);

        return redirect()->back()->with('message', 'Application updated.');
    }

    public function downloadResume(int $applicationId)
    {
        $auth = Services::portalAuth();
        $app  = model(JobApplicationModel::class, false)->find($applicationId);

        if ($app === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! model(JobModel::class)->belongsToEmployer((int) $app['job_id'], (int) $auth->id())) {
            return redirect()->to(site_url(Services::portalLocale()->localizePath('employer')))->with('error', 'Not allowed.');
        }

        $relative = $app['resume_path'];
        if ($relative === '') {
            return redirect()->back()->with('error', 'No resume on file.');
        }

        $path = WRITEPATH . 'uploads/' . $relative;
        if (! is_file($path)) {
            return redirect()->back()->with('error', 'File missing.');
        }

        return $this->response->download($path, null);
    }
}
