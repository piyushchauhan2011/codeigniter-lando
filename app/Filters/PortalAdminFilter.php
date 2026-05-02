<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\Shield\Config\Services as ShieldServices;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class PortalAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ResponseInterface|string|null
    {
        if (! ShieldServices::auth()->user()?->can('admin.access')) {
            return redirect()->to(site_url(Services::portalLocale()->localizePath('dashboard')))->with('error', 'Admin account required.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
