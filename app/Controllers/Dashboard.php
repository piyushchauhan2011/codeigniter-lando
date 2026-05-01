<?php

declare(strict_types=1);

namespace App\Controllers;

use Config\Services;

class Dashboard extends BaseController
{
    public function index()
    {
        $auth = Services::portalAuth();

        if ($auth->isEmployer()) {
            return redirect()->to(site_url('employer'));
        }

        if ($auth->isSeeker()) {
            return redirect()->to(site_url('seeker'));
        }

        return redirect()->to(site_url('login'))->with('error', 'Unable to resolve dashboard.');
    }
}
