<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\ObjectStorage;
use Config\Services;

class RustfsSmoke extends BaseCommand
{
    protected $group = 'JobPortal';

    protected $name = 'rustfs:smoke';

    protected $description = 'Upload, sign, and delete a tiny object through the configured RustFS/S3 service.';

    protected $usage = 'rustfs:smoke';

    public function run(array $params): void
    {
        $config  = config(ObjectStorage::class);
        $storage = Services::objectStorage();
        $key     = 'smoke-tests/' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.txt';
        $path    = tempnam(sys_get_temp_dir(), 'rustfs-smoke-');

        if ($path === false) {
            CLI::error('Could not create a temporary file.');

            return;
        }

        file_put_contents($path, 'RustFS smoke upload from CodeIgniter at ' . date(DATE_ATOM));

        try {
            $storage->ensureBucket();
            $storage->putObject($key, $path, 'text/plain', ['source' => 'spark-smoke']);
            $url = $storage->temporaryUrl($key, $config->signedUrlTtl, 'rustfs-smoke.txt');
            $storage->deleteObject($key);
        } catch (\Throwable $exception) {
            CLI::error('RustFS smoke failed: ' . $exception->getMessage());

            return;
        } finally {
            @unlink($path);
        }

        CLI::write('RustFS bucket: ' . $storage->bucket(), 'green');
        CLI::write('Uploaded and deleted object: ' . $key, 'green');
        CLI::write('Example signed URL: ' . $url, 'yellow');
    }
}
