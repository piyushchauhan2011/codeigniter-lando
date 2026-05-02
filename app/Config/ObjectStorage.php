<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ObjectStorage extends BaseConfig
{
    public string $endpoint = 'http://rustfs:9000';

    public string $publicEndpoint = 'http://rustfs-api.my-first-lamp-app.lndo.site:8000';

    public string $region = 'us-east-1';

    public string $accessKey = 'rustfsadmin';

    public string $secretKey = 'rustfsadmin';

    public string $bucket = 'job-portal-assets';

    public int $signedUrlTtl = 300;

    public bool $usePathStyleEndpoint = true;

    public function __construct()
    {
        parent::__construct();

        $this->endpoint             = (string) env('objectStorage.endpoint', $this->endpoint);
        $this->publicEndpoint       = (string) env('objectStorage.publicEndpoint', $this->publicEndpoint);
        $this->region               = (string) env('objectStorage.region', $this->region);
        $this->accessKey            = (string) env('objectStorage.accessKey', $this->accessKey);
        $this->secretKey            = (string) env('objectStorage.secretKey', $this->secretKey);
        $this->bucket               = (string) env('objectStorage.bucket', $this->bucket);
        $this->signedUrlTtl         = (int) env('objectStorage.signedUrlTtl', $this->signedUrlTtl);
        $this->usePathStyleEndpoint = filter_var(env('objectStorage.usePathStyleEndpoint', $this->usePathStyleEndpoint), FILTER_VALIDATE_BOOL);
    }
}
