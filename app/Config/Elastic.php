<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Elastic extends BaseConfig
{
    public string $elasticsearchUrl = 'http://elasticsearch:9200';

    public string $kibanaUrl = 'http://kibana:5601';

    public string $apmServerUrl = 'http://apm-server:8200';

    /** Browser RUM posts via same-origin `/__apm-proxy` (see ApmProxy controller). */
    public string $publicApmServerUrl = 'https://my-first-lamp-app.lndo.site/__apm-proxy';

    public string $publicBaseUrl = 'https://my-first-lamp-app.lndo.site';

    public string $jobIndex = 'codeigniter-jobs-v1';

    public string $serviceName = 'codeigniter-job-board';

    public string $serviceVersion = 'local-dev';

    public function __construct()
    {
        parent::__construct();

        $this->elasticsearchUrl  = rtrim((string) env('elastic.elasticsearchUrl', $this->elasticsearchUrl), '/');
        $this->kibanaUrl         = rtrim((string) env('elastic.kibanaUrl', $this->kibanaUrl), '/');
        $this->apmServerUrl      = rtrim((string) env('elastic.apmServerUrl', $this->apmServerUrl), '/');
        $this->publicApmServerUrl = rtrim((string) env('elastic.publicApmServerUrl', $this->publicApmServerUrl), '/');
        $this->publicBaseUrl     = rtrim((string) env('elastic.publicBaseUrl', $this->publicBaseUrl), '/');
        $this->jobIndex          = (string) env('elastic.jobIndex', $this->jobIndex);
        $this->serviceName       = (string) env('elastic.serviceName', $this->serviceName);
        $this->serviceVersion    = (string) env('elastic.serviceVersion', $this->serviceVersion);
    }
}
