<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Config\Services as ShieldServices;
use Config\Services;

class PortalAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ResponseInterface|string|null
    {
        if (! ShieldServices::auth()->loggedIn()) {
            return redirect()->to(site_url(Services::portalLocale()->localizePath('login')))->with('error', 'Please sign in to continue.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
