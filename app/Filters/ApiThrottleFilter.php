<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Api as ApiConfig;
use Config\Services;

class ApiThrottleFilter implements FilterInterface
{
    private const WINDOW_SECONDS = 60;

    public function before(RequestInterface $request, $arguments = null): ResponseInterface|string|null
    {
        if (ENVIRONMENT === 'testing') {
            return null;
        }

        $maxPerMinute = config(ApiConfig::class)->throttlePerMinute;
        if ($maxPerMinute <= 0) {
            return null;
        }

        $ip   = $request->getIPAddress();
        $path = $request->getUri()->getPath();
        $key  = 'api_throttle_' . hash('sha256', $ip . '|' . $path);

        $cache = cache();
        /** @var array{count:int, window_start:int}|null $bucket */
        $bucket = $cache->get($key);
        $now = time();

        if (! is_array($bucket)) {
            $bucket = ['count' => 0, 'window_start' => $now];
        } elseif (($now - $bucket['window_start']) >= self::WINDOW_SECONDS) {
            $bucket = ['count' => 0, 'window_start' => $now];
        }

        $bucket['count']++;

        $cache->save($key, $bucket, self::WINDOW_SECONDS + 10);

        if ($bucket['count'] > $maxPerMinute) {
            return Services::response()
                ->setStatusCode(429)
                ->setJSON([
                    'error' => [
                        'code'    => 'too_many_requests',
                        'message' => 'Rate limit exceeded. Retry later.',
                    ],
                ])
                ->setHeader('Retry-After', (string) self::WINDOW_SECONDS);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
