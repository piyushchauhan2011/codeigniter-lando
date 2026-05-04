<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\JobPortalDemoSeeder;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * IDOR-style checks: owning employer_user_id gates mutations even when session is authenticated.
 *
 * @internal
 */
final class PortalAuthorizationFeatureTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;

    protected $refresh = true;

    protected $seed = JobPortalDemoSeeder::class;

    protected function tearDown(): void
    {
        Services::resetSingle('auth');

        parent::tearDown();
    }

    public function testEmployerGets404WhenEditingAnotherEmployersJob(): void
    {
        $victimId  = $this->createEmployerUser('intruder-target@example.test');
        $victimJob = $this->insertPublishedJobForEmployer($victimId);

        $employer = $this->employerUser('employer@example.test');

        try {
            $this->actingAs($employer)
                ->get('/employer/jobs/' . $victimJob . '/edit');
            self::fail('Expected PageNotFoundException.');
        } catch (PageNotFoundException $exception) {
            self::assertSame(404, $exception->getCode());
        }
    }

    public function testEmployerGets404WhenUpdatingAnotherEmployersJob(): void
    {
        $victimId   = $this->createEmployerUser('intruder-update@example.test');
        $victimJob  = $this->insertPublishedJobForEmployer($victimId);

        $employer = $this->employerUser('employer@example.test');

        try {
            $this->actingAs($employer)
                ->post('/employer/jobs/' . $victimJob, [
                    'title'           => 'Should not persist',
                    'description'     => str_repeat('Describing enough chars here. ', 3),
                    'employment_type' => 'full_time',
                    'location'        => 'Remote',
                    'salary_min'      => '',
                    'salary_max'      => '',
                    'category_id'     => '',
                    'status'          => 'published',
                ]);
            self::fail('Expected PageNotFoundException.');
        } catch (PageNotFoundException $exception) {
            self::assertSame(404, $exception->getCode());
        }

        $title = $this->db->table('portal_jobs')->select('title')->where('id', $victimJob)->get()->getRow('title');
        self::assertSame('Victim listing', (string) $title);
    }

    private function createEmployerUser(string $email): int
    {
        /** @var UserModel $users */
        $users = model(UserModel::class, false);
        $seen  = $users->findByCredentials(['email' => $email]);
        if ($seen !== null) {
            return (int) $seen->id;
        }

        $user = $users->createNewUser([
            'active'   => 0,
            'email'    => $email,
            'password' => 'password123',
        ]);
        $users->save($user);

        $user = $users->findById($users->getInsertID());
        if ($user === null) {
            self::fail('Could not reload employer user.');
        }

        $user->addGroup('employer');
        $user->activate();

        return (int) $user->id;
    }

    private function insertPublishedJobForEmployer(int $employerUserId): int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('portal_jobs')->insert([
            'employer_user_id' => $employerUserId,
            'category_id' => null,
            'title'            => 'Victim listing',
            'description'      => 'Owned by another employer for authorization testing.',
            'employment_type'  => 'full_time',
            'location'         => 'Remote',
            'salary_min'       => null,
            'salary_max'       => null,
            'status'           => 'published',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        return (int) $this->db->insertID();
    }

    private function employerUser(string $email): User
    {
        $user = model(UserModel::class, false)->findById($this->userIdForEmail($email));
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    private function userIdForEmail(string $email): int
    {
        return (int) $this->db->table('auth_identities')
            ->select('user_id')
            ->where('type', 'email_password')
            ->where('secret', $email)
            ->get()
            ->getRow('user_id');
    }
}
