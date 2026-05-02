<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Events\Events;
use CodeIgniter\Queue\Config\Services as QueueServices;
use CodeIgniter\Settings\Config\Services as SettingsServices;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\TestLogger;
use CodeIgniter\Tasks\Config\Services as TaskServices;
use CodeIgniter\Tasks\TaskRunner;
use Config\Logger;
use Config\Services;
use ReflectionProperty;

/**
 * @internal
 */
final class JobPortalQueueAndTasksTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    protected $refresh = true;

    protected $seed = \App\Database\Seeds\JobPortalDemoSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        Services::injectMock('logger', new TestLogger(new Logger()));
    }

    protected function tearDown(): void
    {
        $prop = new ReflectionProperty(TestLogger::class, 'op_logs');
        $prop->setAccessible(true);
        $prop->setValue(null, []);

        parent::tearDown();
    }

    public function testNotifyEmployerJobCanBePushedProcessedAndRemoved(): void
    {
        $jobId    = (int) $this->db->table('portal_jobs')->select('id')->where('title', 'Senior PHP Engineer')->get()->getRow('id');
        $seekerId = $this->userIdForEmail('seeker@example.test');

        self::assertGreaterThan(0, $jobId);
        self::assertGreaterThan(0, $seekerId);

        $queue = QueueServices::queue();
        $push  = $queue->push('default', 'notify-employer-application', [
            'job_id'           => $jobId,
            'seeker_user_id'   => $seekerId,
        ]);

        self::assertTrue($push->getStatus(), $push->getError() ?? 'queue push failed');
        self::assertSame(1, $this->db->table('queue_jobs')->countAllResults());

        $work = $queue->pop('default', ['default']);
        self::assertNotNull($work);

        $payload = $work->payload;
        $class   = config('Queue')->resolveJobClass($payload['job']);
        $job     = new $class($payload['data']);
        $job->process();

        self::assertTrue($queue->done($work));
        self::assertSame(0, $this->db->table('queue_jobs')->countAllResults());

        $this->assertLogContains('info', '[Employer notify queued]');
    }

    public function testJobApplicationSubmittedEventEnqueuesNotifyEmployerJob(): void
    {
        $jobId    = (int) $this->db->table('portal_jobs')->select('id')->where('title', 'Senior PHP Engineer')->get()->getRow('id');
        $seekerId = $this->userIdForEmail('seeker@example.test');

        Events::trigger('job_application_submitted', $jobId, $seekerId);

        self::assertSame(1, $this->db->table('queue_jobs')->countAllResults());

        $row = $this->db->table('queue_jobs')->get()->getRowArray();
        self::assertIsArray($row);

        $payload = json_decode((string) $row['payload'], true);
        self::assertIsArray($payload);
        self::assertSame('notify-employer-application', $payload['job']);
        self::assertSame($jobId, $payload['data']['job_id']);
        self::assertSame($seekerId, $payload['data']['seeker_user_id']);
    }

    public function testTaskRunnerRunsJobportalHousekeepingCommand(): void
    {
        SettingsServices::settings()->set('Tasks.enabled', true);

        config('Tasks')->init(TaskServices::scheduler());

        $runner = new TaskRunner();
        $runner->only(['jobportal-housekeeping']);
        $runner->run();

        $this->assertLogContains('info', '[Job portal housekeeping] published_jobs=');
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
