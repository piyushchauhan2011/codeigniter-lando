<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Models\UserModel;

class JobPortalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $categories = [
            ['slug' => 'engineering', 'name' => 'Engineering'],
            ['slug' => 'sales', 'name' => 'Sales'],
            ['slug' => 'remote', 'name' => 'Remote'],
        ];

        foreach ($categories as $row) {
            $this->ensureCategory($row['slug'], $row['name'], $now);
        }

        $engineeringId = (int) $this->db->table('job_categories')->where('slug', 'engineering')->get()->getRow('id');

        $employerId = $this->createShieldUser('employer@example.test', 'password123', 'employer');
        $seekerId   = $this->createShieldUser('seeker@example.test', 'password123', 'seeker');
        $this->createShieldUser('admin@example.test', 'password123', 'admin');

        $this->ensureEmployerProfile($employerId, $now);
        $this->ensureSeekerProfile($seekerId, $now);
        $this->ensureJob([
            'employer_user_id' => $employerId,
            'category_id'      => null,
            'title'            => 'Part-time Technical Writer',
            'description'      => 'Document REST APIs and onboarding guides.',
            'employment_type'  => 'part_time',
            'location'         => 'Berlin',
            'salary_min'       => null,
            'salary_max'       => null,
            'status'           => 'published',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
        $this->ensureJob([
            'employer_user_id' => $employerId,
            'category_id'      => $engineeringId > 0 ? $engineeringId : null,
            'title'            => 'Senior PHP Engineer',
            'description'      => 'Build APIs and mentor juniors. Hands-on with CI4 or similar MVC frameworks.',
            'employment_type'  => 'full_time',
            'location'         => 'Remote',
            'salary_min'       => 80000,
            'salary_max'       => 120000,
            'status'           => 'published',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }

    private function createShieldUser(string $email, string $password, string $group): int
    {
        /** @var UserModel $users */
        $users = model(UserModel::class, false);
        $user  = $users->findByCredentials(['email' => $email]);
        if ($user !== null) {
            $user->addGroup($group);
            $user->activate();

            return (int) $user->id;
        }

        $user  = $users->createNewUser([
            'active'   => 0,
            'email'    => $email,
            'password' => $password,
        ]);

        $users->save($user);

        $user = $users->findById($users->getInsertID());
        if ($user === null) {
            throw new \RuntimeException('Unable to create demo Shield user: ' . $email);
        }

        $user->addGroup($group);
        $user->activate();

        return (int) $user->id;
    }

    private function ensureCategory(string $slug, string $name, string $now): void
    {
        $existing = $this->db->table('job_categories')->where('slug', $slug)->get()->getRowArray();
        if ($existing !== null) {
            return;
        }

        $this->db->table('job_categories')->insert([
            'slug'       => $slug,
            'name'       => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureEmployerProfile(int $employerId, string $now): void
    {
        $existing = $this->db->table('employer_profiles')->where('user_id', $employerId)->get()->getRowArray();
        if ($existing !== null) {
            return;
        }

        $this->db->table('employer_profiles')->insert([
            'user_id'      => $employerId,
            'company_name' => 'Demo Corp',
            'website'      => 'https://example.test',
            'description'  => 'Demo employer used for learning.',
            'verified'     => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    private function ensureSeekerProfile(int $seekerId, string $now): void
    {
        $existing = $this->db->table('job_seeker_profiles')->where('user_id', $seekerId)->get()->getRowArray();
        if ($existing !== null) {
            return;
        }

        $this->db->table('job_seeker_profiles')->insert([
            'user_id'    => $seekerId,
            'headline'   => 'PHP developer learning CI4',
            'bio'        => 'Building a job portal tutorial project.',
            'skills'     => 'PHP, MySQL, HTML',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param array<string, int|string|null> $job
     */
    private function ensureJob(array $job): void
    {
        $existing = $this->db->table('portal_jobs')
            ->where('employer_user_id', $job['employer_user_id'])
            ->where('title', $job['title'])
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            return;
        }

        $this->db->table('portal_jobs')->insert($job);
    }
}
