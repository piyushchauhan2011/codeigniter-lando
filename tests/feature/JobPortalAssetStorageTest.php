<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\JobPortalDemoSeeder;
use App\Libraries\ObjectStorage\ObjectStorageClientInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class JobPortalAssetStorageTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;

    protected $refresh = true;

    protected $seed = JobPortalDemoSeeder::class;

    private FakeObjectStorageClient $storage;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('auth');
        Services::resetSingle('objectStorage');

        $this->storage = new FakeObjectStorageClient();
        Services::injectMock('objectStorage', $this->storage);
    }

    protected function tearDown(): void
    {
        service('superglobals')->setFilesArray([]);
        Services::resetSingle('auth');
        Services::resetSingle('objectStorage');

        parent::tearDown();
    }

    public function testEmployerUploadsJobAssetThroughStorageService(): void
    {
        $jobId = $this->firstJobId();
        $this->fakeUpload('demo.txt', 'hello from rustfs learning', 'text/plain');

        $result = $this
            ->withSession(['user' => ['id' => $this->userIdForEmail('employer@example.test')]])
            ->post('/employer/jobs/' . $jobId . '/assets');

        $result->assertRedirectTo('/employer/jobs/' . $jobId . '/assets');

        self::assertSame(1, $this->db->table('job_assets')->countAllResults());
        self::assertCount(1, $this->storage->putObjects);
        self::assertStringStartsWith('job-assets/' . $jobId . '/', $this->storage->putObjects[0]['key']);
        self::assertSame('text/plain', $this->storage->putObjects[0]['contentType']);
    }

    public function testEmployerUploadRejectedWhenDetectedMimeOutsideWhitelist(): void
    {
        $jobId = $this->firstJobId();
        $before = $this->db->table('job_assets')->countAllResults();

        $this->fakeUpload('report.pdf', "<?php echo 'no';", 'application/pdf');

        $result = $this
            ->withSession(['user' => ['id' => $this->userIdForEmail('employer@example.test')]])
            ->post('/employer/jobs/' . $jobId . '/assets');

        $result->assertRedirect();

        self::assertSame($before, $this->db->table('job_assets')->countAllResults());
        self::assertCount(0, $this->storage->putObjects);
    }

    public function testEmployerGetsSignedUrlForOwnedAsset(): void
    {
        $assetId = $this->insertAssetForEmployer('offer.pdf');

        $result = $this
            ->withSession(['user' => ['id' => $this->userIdForEmail('employer@example.test')]])
            ->get('/employer/assets/' . $assetId . '/signed-url');

        $result->assertRedirectTo('https://signed.example.test/job-assets/1/offer.pdf?ttl=300&download=offer.pdf');
        self::assertSame(['job-assets/1/offer.pdf'], $this->storage->signedKeys);
    }

    public function testEmployerDeletesOwnedAssetFromStorageAndDatabase(): void
    {
        $assetId = $this->insertAssetForEmployer('delete-me.txt');

        $result = $this
            ->withSession(['user' => ['id' => $this->userIdForEmail('employer@example.test')]])
            ->post('/employer/assets/' . $assetId . '/delete');

        $result->assertRedirect();
        self::assertSame(['job-assets/1/delete-me.txt'], $this->storage->deletedKeys);
        self::assertSame(0, $this->db->table('job_assets')->where('id', $assetId)->countAllResults());
    }

    public function testSeekerCannotAccessEmployerAssetArea(): void
    {
        $result = $this
            ->withSession(['user' => ['id' => $this->userIdForEmail('seeker@example.test')]])
            ->get('/employer/jobs/' . $this->firstJobId() . '/assets');

        $result->assertRedirectTo('/dashboard');
    }

    public function testGuestCannotAccessEmployerAssetArea(): void
    {
        $result = $this->get('/employer/jobs/' . $this->firstJobId() . '/assets');

        $result->assertRedirectTo('/login');
    }

    private function fakeUpload(string $name, string $contents, string $mime): void
    {
        $path = tempnam(sys_get_temp_dir(), 'job-asset-');
        if ($path === false) {
            self::fail('Could not create a temporary upload file.');
        }

        file_put_contents($path, $contents);

        service('superglobals')->setFilesArray([
            'asset' => [
                'name'     => $name,
                'type'     => $mime,
                'tmp_name' => $path,
                'error'    => UPLOAD_ERR_OK,
                'size'     => strlen($contents),
            ],
        ]);
    }

    private function insertAssetForEmployer(string $name): int
    {
        $employerId = $this->userIdForEmail('employer@example.test');

        $this->db->table('job_assets')->insert([
            'job_id'           => $this->firstJobId(),
            'employer_user_id' => $employerId,
            'bucket'           => 'job-portal-assets',
            'object_key'       => 'job-assets/1/' . $name,
            'original_name'    => $name,
            'mime_type'        => 'text/plain',
            'size_bytes'       => 24,
            'visibility'       => 'private',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    private function firstJobId(): int
    {
        return (int) $this->db->table('portal_jobs')->select('id')->orderBy('id', 'ASC')->get()->getRow('id');
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

final class FakeObjectStorageClient implements ObjectStorageClientInterface
{
    /** @var list<array{key: string, sourcePath: string, contentType: string, metadata: array<string, string>}> */
    public array $putObjects = [];

    /** @var list<string> */
    public array $signedKeys = [];

    /** @var list<string> */
    public array $deletedKeys = [];

    public function ensureBucket(): void
    {
    }

    /**
     * @param array<string, string> $metadata
     */
    public function putObject(string $key, string $sourcePath, string $contentType, array $metadata = []): void
    {
        $this->putObjects[] = [
            'key'         => $key,
            'sourcePath'  => $sourcePath,
            'contentType' => $contentType,
            'metadata'    => $metadata,
        ];
    }

    public function temporaryUrl(string $key, int $ttlSeconds, string $downloadName = ''): string
    {
        $this->signedKeys[] = $key;

        return 'https://signed.example.test/' . $key . '?ttl=' . $ttlSeconds . '&download=' . rawurlencode($downloadName);
    }

    public function deleteObject(string $key): void
    {
        $this->deletedKeys[] = $key;
    }

    public function bucket(): string
    {
        return 'job-portal-assets';
    }
}
