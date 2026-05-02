<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\JobAssetModel;
use App\Models\JobModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\ObjectStorage;
use Config\Services;

class EmployerAssets extends BaseController
{
    public function index(int $jobId): string
    {
        $auth = Services::portalAuth();
        $job  = $this->findOwnedJob($jobId, (int) $auth->id());

        return view('portal/employer/job_assets', [
            'title'   => 'Job assets',
            'job'     => $job,
            'assets'  => model(JobAssetModel::class)->findAllForEmployerJob($jobId, (int) $auth->id()),
            'errors'  => session()->getFlashdata('errors') ?? [],
            'storage' => config(ObjectStorage::class),
        ]);
    }

    public function create(int $jobId)
    {
        $auth = Services::portalAuth();
        $this->findOwnedJob($jobId, (int) $auth->id());

        if (! $this->validate('portal_job_asset_upload')) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('asset');
        $validUpload = $file !== null && ($file->isValid() || (ENVIRONMENT === 'testing' && $file->getError() === UPLOAD_ERR_OK));
        if (! $validUpload) {
            return redirect()->back()->withInput()->with('error', 'Choose a file to upload.');
        }

        $extension = $file->getClientExtension();
        $key       = 'job-assets/' . $jobId . '/' . date('YmdHis') . '-' . bin2hex(random_bytes(8));
        if ($extension !== '') {
            $key .= '.' . strtolower($extension);
        }

        $storage = Services::objectStorage();

        try {
            $storage->ensureBucket();
            $storage->putObject($key, $file->getTempName(), $file->getClientMimeType(), [
                'job_id'      => (string) $jobId,
                'uploaded_by' => (string) $auth->id(),
                'source'      => 'codeigniter-job-portal',
            ]);
        } catch (\Throwable $exception) {
            log_message('error', 'RustFS upload failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'Could not upload to object storage.');
        }

        model(JobAssetModel::class, false)->insert([
            'job_id'           => $jobId,
            'employer_user_id' => $auth->id(),
            'bucket'           => $storage->bucket(),
            'object_key'       => $key,
            'original_name'    => $file->getClientName(),
            'mime_type'        => $file->getClientMimeType(),
            'size_bytes'       => $file->getSize(),
            'visibility'       => 'private',
        ]);

        return redirect()->to(site_url(Services::portalLocale()->localizePath('employer/jobs/' . $jobId . '/assets')))
            ->with('message', 'Uploaded asset to RustFS.');
    }

    public function signedUrl(int $assetId)
    {
        $auth  = Services::portalAuth();
        $asset = model(JobAssetModel::class)->findForEmployer($assetId, (int) $auth->id());
        if ($asset === null || ! model(JobModel::class)->belongsToEmployer((int) $asset['job_id'], (int) $auth->id())) {
            throw PageNotFoundException::forPageNotFound();
        }

        $ttl = config(ObjectStorage::class)->signedUrlTtl;
        $url = Services::objectStorage()->temporaryUrl((string) $asset['object_key'], $ttl, (string) $asset['original_name']);

        return redirect()->to($url);
    }

    public function delete(int $assetId)
    {
        $auth  = Services::portalAuth();
        $asset = model(JobAssetModel::class)->findForEmployer($assetId, (int) $auth->id());
        if ($asset === null || ! model(JobModel::class)->belongsToEmployer((int) $asset['job_id'], (int) $auth->id())) {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            Services::objectStorage()->deleteObject((string) $asset['object_key']);
        } catch (\Throwable $exception) {
            log_message('error', 'RustFS delete failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->back()->with('error', 'Could not delete from object storage.');
        }

        model(JobAssetModel::class, false)->delete((int) $asset['id']);

        return redirect()->back()->with('message', 'Deleted asset from RustFS.');
    }

    /**
     * @return array<string, mixed>
     */
    private function findOwnedJob(int $jobId, int $employerUserId): array
    {
        $job = model(JobModel::class, false)->find($jobId);
        if ($job === null || (int) $job['employer_user_id'] !== $employerUserId) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $job;
    }
}
