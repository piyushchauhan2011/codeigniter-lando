<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\LoginThrottle as LoginThrottleConfig;
use Config\Services;

class LoginThrottleFilter implements FilterInterface
{
    private const WINDOW_SECONDS = 60;

    public function before(RequestInterface $request, $arguments = null): ResponseInterface|string|null
    {
        if (ENVIRONMENT === 'testing') {
            return null;
        }

        $maxPerMinute = config(LoginThrottleConfig::class)->maxAttemptsPerMinute;
        if ($maxPerMinute <= 0) {
            return null;
        }

        $ip  = $request->getIPAddress();
        $key = 'login_throttle_' . hash('sha256', $ip);

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
            return redirect()->back()->with(
                'error',
                'Too many login attempts from this network. Wait a minute and try again.',
            );
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
