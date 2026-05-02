<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\JobPortalDemoSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class JobPortalShieldAuthTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;

    protected $refresh = true;

    protected $seed = JobPortalDemoSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('auth');
    }

    protected function tearDown(): void
    {
        Services::resetSingle('auth');

        parent::tearDown();
    }

    public function testGuestDashboardRedirectsToLogin(): void
    {
        $result = $this->get('/dashboard');

        $result->assertRedirectTo('/login');
    }

    public function testLoginUsesShieldRememberMe(): void
    {
        $result = $this->post('/login', [
            'email'    => 'seeker@example.test',
            'password' => 'password123',
            'remember' => '1',
        ]);

        $result->assertRedirectTo('/dashboard');
        self::assertSame(1, $this->db->table('auth_remember_tokens')->countAllResults());
    }

    public function testSeekerCannotAccessEmployerArea(): void
    {
        $result = $this
            ->withSession(['user' => ['id' => $this->userIdForEmail('seeker@example.test')]])
            ->get('/employer');

        $result->assertRedirectTo('/dashboard');
    }

    public function testAdminCanAccessAdminDashboard(): void
    {
        $result = $this
            ->withSession(['user' => ['id' => $this->userIdForEmail('admin@example.test')]])
            ->get('/admin');

        $result->assertOK();
        $result->assertSee('Shield users');
        $result->assertSee('admin@example.test');
    }

    public function testRegistrationRequiresEmailVerification(): void
    {
        $result = $this->post('/register', [
            'email'            => 'new-seeker@example.test',
            'password'         => 'S0lid-Passphrase-2026!',
            'password_confirm' => 'S0lid-Passphrase-2026!',
            'role'             => 'seeker',
            'company_name'     => '',
        ]);

        $result->assertRedirectTo('/auth/a/show');

        $row = $this->db->table('auth_identities')
            ->select('users.active, auth_identities.user_id')
            ->join('users', 'users.id = auth_identities.user_id')
            ->where('auth_identities.type', 'email_password')
            ->where('auth_identities.secret', 'new-seeker@example.test')
            ->get()
            ->getRowArray();

        self::assertIsArray($row);
        self::assertSame('0', (string) $row['active']);
        self::assertSame(1, $this->db->table('auth_identities')->where('user_id', $row['user_id'])->where('type', 'email_activate')->countAllResults());
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
