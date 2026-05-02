<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class RequestTelemetryFilter implements FilterInterface
{
    private const REQUEST_ID_ATTRIBUTE = 'ELK_LAB_REQUEST_ID';

    public function before(RequestInterface $request, $arguments = null)
    {
        Services::superglobals()
            ->setServer(self::REQUEST_ID_ATTRIBUTE, $this->requestId($request))
            ->setServer('ELK_LAB_REQUEST_STARTED_AT', (string) microtime(true));

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $superglobals = Services::superglobals();
        $requestId = (string) $superglobals->server(self::REQUEST_ID_ATTRIBUTE, $this->requestId($request));
        $startedAt = (float) $superglobals->server(
            'ELK_LAB_REQUEST_STARTED_AT',
            $superglobals->server('REQUEST_TIME_FLOAT', microtime(true)),
        );
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $auth = Services::portalAuth();

        $response->setHeader('X-Request-Id', $requestId);

        log_message('info', json_encode([
            'message' => 'HTTP request completed',
            'event' => [
                'dataset'  => 'codeigniter.request',
                'duration' => $durationMs * 1_000_000,
            ],
            'http' => [
                'request' => [
                    'method' => $request->getMethod(),
                    'id'     => $requestId,
                ],
                'response' => [
                    'status_code' => $response->getStatusCode(),
                ],
            ],
            'url' => [
                'path'   => '/' . ltrim($request->getUri()->getPath(), '/'),
                'query'  => $request->getUri()->getQuery(),
                'domain' => $request->getUri()->getHost(),
            ],
            'user' => [
                'id'    => $auth->check() ? (string) $auth->id() : null,
                'roles' => $this->roles(),
            ],
            'labels' => [
                'duration_ms' => $durationMs,
            ],
        ], JSON_THROW_ON_ERROR));

        return null;
    }

    private function requestId(RequestInterface $request): string
    {
        $incoming = trim($request->getHeaderLine('X-Request-Id'));
        if ($incoming !== '') {
            return substr($incoming, 0, 128);
        }

        return bin2hex(random_bytes(12));
    }

    /**
     * @return list<string>
     */
    private function roles(): array
    {
        $auth  = Services::portalAuth();
        $roles = [];

        if (! $auth->check()) {
            return $roles;
        }

        if ($auth->isAdmin()) {
            $roles[] = 'admin';
        }

        if ($auth->isEmployer()) {
            $roles[] = 'employer';
        }

        if ($auth->isSeeker()) {
            $roles[] = 'seeker';
        }

        return $roles;
    }
}
