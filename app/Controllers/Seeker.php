<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\JobApplicationModel;
use App\Models\JobModel;
use App\Models\JobSeekerProfileModel;
use App\Models\SavedJobModel;
use CodeIgniter\Events\Events;
use Config\Services;

class Seeker extends BaseController
{
    public function dashboard(): string
    {
        $auth = Services::portalAuth();

        $applications = model(JobApplicationModel::class)->findAllForSeeker((int) $auth->id());

        return view('portal/seeker/dashboard', [
            'title'          => 'Seeker dashboard',
            'applications' => $applications,
        ]);
    }

    public function profile(): string
    {
        $auth    = Services::portalAuth();
        $profile = model(JobSeekerProfileModel::class, false)->where('user_id', $auth->id())->first();

        if ($profile === null) {
            throw new \RuntimeException('Seeker profile missing.');
        }

        return view('portal/seeker/profile', [
            'title'   => 'Your profile',
            'profile' => $profile,
            'errors'  => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function updateProfile()
    {
        if (! $this->validate('portal_seeker_profile')) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $auth    = Services::portalAuth();
        $profile = model(JobSeekerProfileModel::class, false)->where('user_id', $auth->id())->first();

        if ($profile === null) {
            return redirect()->back()->with('error', 'Profile not found.');
        }

        $data = [
            'headline' => (string) $this->request->getPost('headline'),
            'bio'      => (string) $this->request->getPost('bio'),
            'skills'   => (string) $this->request->getPost('skills'),
        ];

        $file = $this->request->getFile('resume');
        if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
            if (! $this->validate([
                'resume' => 'uploaded[resume]|max_size[resume,4096]|ext_in[resume,pdf,doc,docx]',
            ])) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $target = WRITEPATH . 'uploads/job_portal/resumes/';
            if (! is_dir($target) && ! mkdir($target, 0775, true) && ! is_dir($target)) {
                return redirect()->back()->with('error', 'Cannot create upload directory.');
            }

            $newName = $file->getRandomName();
            $file->move($target, $newName);
            $data['resume_path'] = 'job_portal/resumes/' . $newName;
        }

        model(JobSeekerProfileModel::class, false)->update($profile['id'], $data);

        return redirect()->to(site_url(Services::portalLocale()->localizePath('seeker/profile')))->with('message', 'Profile updated.');
    }

    public function applications(): string
    {
        $auth = Services::portalAuth();

        $applications = model(JobApplicationModel::class)->findAllForSeeker((int) $auth->id());

        return view('portal/seeker/applications', [
            'title'        => lang('Portal.applications_heading'),
            'applications' => $applications,
        ]);
    }

    public function apply(int $jobId)
    {
        $auth = Services::portalAuth();

        $job = model(JobModel::class, false)->find($jobId);
        if ($job === null || $job['status'] !== 'published') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (model(JobApplicationModel::class)->hasApplied($jobId, (int) $auth->id())) {
            return redirect()->back()->with('error', 'You already applied to this job.');
        }

        if (! $this->validate('portal_apply')) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $profile = model(JobSeekerProfileModel::class, false)->where('user_id', $auth->id())->first();
        $resume  = $profile['resume_path'] ?? null;

        $file = $this->request->getFile('resume');
        if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
            if (! $this->validate([
                'resume' => 'uploaded[resume]|max_size[resume,4096]|ext_in[resume,pdf,doc,docx]',
            ])) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $target = WRITEPATH . 'uploads/job_portal/resumes/';
            if (! is_dir($target) && ! mkdir($target, 0775, true) && ! is_dir($target)) {
                return redirect()->back()->with('error', 'Cannot create upload directory.');
            }

            $newName    = $file->getRandomName();
            $file->move($target, $newName);
            $resume = 'job_portal/resumes/' . $newName;
        }

        if ($resume === null || $resume === '') {
            return redirect()->back()->withInput()->with('error', 'Add a resume on your profile or upload one with this application.');
        }

        $db = db_connect();
        $db->transStart();

        $appModel = model(JobApplicationModel::class, false);
        $appModel->insert([
            'job_id'         => $jobId,
            'seeker_user_id' => $auth->id(),
            'cover_letter'   => (string) $this->request->getPost('cover_letter'),
            'resume_path'    => $resume,
            'status'         => 'submitted',
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->with('error', 'Could not submit application.');
        }

        Events::trigger('job_application_submitted', $jobId, (int) $auth->id());

        return redirect()->to(site_url(Services::portalLocale()->localizePath('jobs/' . $jobId)))->with('message', 'Application submitted.');
    }

    public function toggleSave(int $jobId)
    {
        $auth = Services::portalAuth();

        $job = model(JobModel::class, false)->where('id', $jobId)->where('status', 'published')->first();
        if ($job === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $saved = model(SavedJobModel::class)->toggle((int) $auth->id(), $jobId);

        return redirect()->back()->with('message', $saved ? 'Job saved to your list.' : 'Removed from saved jobs.');
    }
}
