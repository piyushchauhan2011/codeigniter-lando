<?php

declare(strict_types=1);

namespace App\Services\JobPortal;

use App\Models\EmployerProfileModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class EmployerProfileService
{
    /**
     * @return array<string, mixed>|null
     */
    public function findByUserId(int $userId): ?array
    {
        $row = model(EmployerProfileModel::class, false)->where('user_id', $userId)->first();

        return $row !== null ? $row : null;
    }

    /**
     * @param array{company_name: string, website: string, description: string} $fields
     */
    public function update(int $profileId, array $fields, ?string $logoRelativePath = null): void
    {
        $data = $fields;
        if ($logoRelativePath !== null) {
            $data['logo_path'] = $logoRelativePath;
        }

        model(EmployerProfileModel::class, false)->update($profileId, $data);
    }

    /**
     * Persist an already-validated logo upload; returns path relative to FCPATH uploads/.
     */
    public function storeValidatedLogo(UploadedFile $file): string
    {
        $target = FCPATH . 'uploads/job_portal/logos/';
        if (! is_dir($target) && ! mkdir($target, 0775, true) && ! is_dir($target)) {
            throw new \RuntimeException('Cannot create upload directory.');
        }

        $newName = $file->getRandomName();
        $file->move($target, $newName);

        return 'job_portal/logos/' . $newName;
    }
}
