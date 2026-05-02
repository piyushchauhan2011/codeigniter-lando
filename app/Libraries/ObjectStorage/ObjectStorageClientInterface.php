<?php

declare(strict_types=1);

namespace App\Libraries\ObjectStorage;

interface ObjectStorageClientInterface
{
    public function ensureBucket(): void;

    /**
     * @param array<string, string> $metadata
     */
    public function putObject(string $key, string $sourcePath, string $contentType, array $metadata = []): void;

    public function temporaryUrl(string $key, int $ttlSeconds, string $downloadName = ''): string;

    public function deleteObject(string $key): void;

    public function bucket(): string;
}
