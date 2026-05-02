<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\JobModel;
use App\Models\JobSeekerProfileModel;
use CodeIgniter\Queue\BaseJob;
use CodeIgniter\Shield\Models\UserModel;

class NotifyEmployerNewApplication extends BaseJob
{
    public function process(): void
    {
        $jobId        = (int) ($this->data['job_id'] ?? 0);
        $seekerUserId = (int) ($this->data['seeker_user_id'] ?? 0);

        if ($jobId < 1 || $seekerUserId < 1) {
            log_message('warning', 'NotifyEmployerNewApplication: invalid payload {data}', ['data' => $this->data]);

            return;
        }

        $job = model(JobModel::class, false)->find($jobId);
        if ($job === null) {
            log_message('warning', 'NotifyEmployerNewApplication: job not found job_id={id}', ['id' => $jobId]);

            return;
        }

        $employerUserId = (int) $job['employer_user_id'];

        $employer = model(UserModel::class, false)->findById($employerUserId);
        $seeker   = model(UserModel::class, false)->findById($seekerUserId);
        $profile  = model(JobSeekerProfileModel::class, false)->where('user_id', $seekerUserId)->first();

        $employerEmail = $employer->email ?? 'unknown';
        $seekerEmail   = $seeker->email ?? 'unknown';
        $headline      = $profile['headline'] ?? '';

        log_message(
            'info',
            '[Employer notify queued] New application for "{title}" (job_id={job}). Employer contact email on file: {employer_email}. Applicant: {seeker_email} (headline: {headline}).',
            [
                'title'           => $job['title'],
                'job'             => $jobId,
                'employer_email'  => $employerEmail,
                'seeker_email'    => $seekerEmail,
                'headline'        => $headline,
            ],
        );
    }
}
