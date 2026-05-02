<?php

declare(strict_types=1);

namespace App\Libraries\ObjectStorage;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Config\ObjectStorage;

class AwsS3ObjectStorageClient implements ObjectStorageClientInterface
{
    private S3Client $client;

    private S3Client $presignClient;

    public function __construct(private readonly ObjectStorage $config)
    {
        $this->client        = new S3Client($this->clientOptions($config->endpoint));
        $this->presignClient = new S3Client($this->clientOptions($config->publicEndpoint));
    }

    public function ensureBucket(): void
    {
        try {
            $this->client->headBucket(['Bucket' => $this->config->bucket]);

            return;
        } catch (AwsException $exception) {
            if (! in_array($exception->getStatusCode(), [0, 404], true)) {
                throw $exception;
            }
        }

        $this->client->createBucket(['Bucket' => $this->config->bucket]);
        $this->client->waitUntil('BucketExists', ['Bucket' => $this->config->bucket]);
    }

    /**
     * @param array<string, string> $metadata
     */
    public function putObject(string $key, string $sourcePath, string $contentType, array $metadata = []): void
    {
        $this->client->putObject([
            'Bucket'      => $this->config->bucket,
            'Key'         => $key,
            'SourceFile'  => $sourcePath,
            'ContentType' => $contentType,
            'Metadata'    => $metadata,
        ]);
    }

    public function temporaryUrl(string $key, int $ttlSeconds, string $downloadName = ''): string
    {
        $params = [
            'Bucket' => $this->config->bucket,
            'Key'    => $key,
        ];

        if ($downloadName !== '') {
            $params['ResponseContentDisposition'] = 'attachment; filename="' . addcslashes($downloadName, '"\\') . '"';
        }

        $command = $this->presignClient->getCommand('GetObject', $params);
        $request = $this->presignClient->createPresignedRequest($command, '+' . $ttlSeconds . ' seconds');

        return (string) $request->getUri();
    }

    public function deleteObject(string $key): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->config->bucket,
            'Key'    => $key,
        ]);
    }

    public function bucket(): string
    {
        return $this->config->bucket;
    }

    /**
     * @return array<string, mixed>
     */
    private function clientOptions(string $endpoint): array
    {
        return [
            'version'                 => 'latest',
            'region'                  => $this->config->region,
            'endpoint'                => $endpoint,
            'use_path_style_endpoint' => $this->config->usePathStyleEndpoint,
            'credentials'             => [
                'key'    => $this->config->accessKey,
                'secret' => $this->config->secretKey,
            ],
        ];
    }
}
