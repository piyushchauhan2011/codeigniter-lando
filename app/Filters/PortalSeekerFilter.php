<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Config\Services as ShieldServices;
use Config\Services;

class PortalSeekerFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ResponseInterface|string|null
    {
        if (! ShieldServices::auth()->user()?->inGroup('seeker')) {
            return redirect()->to(site_url(Services::portalLocale()->localizePath('dashboard')))->with('error', 'Job seeker account required.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
