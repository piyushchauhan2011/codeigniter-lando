<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Reverse-proxy RUM intake to apm-server using the appserver hostname TLS cert.
 * Direct https://apm.* hits ERR_CERT_COMMON_NAME_INVALID under typical Lando proxies.
 */
class ApmProxy extends BaseController
{
    /** @var list<string> */
    private const FORWARD_HEADERS = [
        'Content-Type',
        'Content-Encoding',
        'Elastic-Apm-Traceparent',
        'Traceparent',
        'Tracestate',
        // Preserve browser context for RUM intake (does not change JS stack filenames; helps upstream behave like a direct browser POST).
        'Origin',
        'Referer',
        'User-Agent',
    ];

    public function forward(string $apiVersion): ResponseInterface
    {
        if ($apiVersion === '' || ! preg_match('/^v\d+$/', $apiVersion)) {
            return $this->response->setStatusCode(404);
        }

        $elastic = config('Elastic');
        $target  = rtrim($elastic->apmServerUrl, '/') . '/intake/' . $apiVersion . '/rum/events';
        $query   = $this->request->getUri()->getQuery();
        if ($query !== '') {
            $target .= '?' . $query;
        }

        $method = strtoupper($this->request->getMethod());

        if ($method === 'OPTIONS') {
            return $this->response->setStatusCode(204)->setHeader('Allow', 'POST, OPTIONS');
        }

        $headers = [];
        foreach (self::FORWARD_HEADERS as $name) {
            $value = $this->request->getHeaderLine($name);
            if ($value !== '') {
                $headers[$name] = $value;
            }
        }

        $options = [
            'headers'     => $headers,
            'http_errors' => false,
            'timeout'     => 30,
        ];

        if (! in_array($method, ['GET', 'HEAD'], true)) {
            $options['body'] = $this->request->getBody() ?? '';
        }

        $upstream = service('curlrequest')->request($method, $target, $options);

        $response = $this->response->setStatusCode($upstream->getStatusCode());

        foreach (['Content-Type', 'Cache-Control'] as $name) {
            if ($upstream->hasHeader($name)) {
                $response->setHeader($name, $upstream->getHeaderLine($name));
            }
        }

        return $response->setBody((string) $upstream->getBody());
    }
}
