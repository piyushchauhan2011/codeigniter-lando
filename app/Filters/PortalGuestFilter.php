<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Config\Services as ShieldServices;
use Config\Services;

class PortalGuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ResponseInterface|string|null
    {
        if (ShieldServices::auth()->loggedIn()) {
            return redirect()->to(site_url(Services::portalLocale()->localizePath('dashboard')));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
