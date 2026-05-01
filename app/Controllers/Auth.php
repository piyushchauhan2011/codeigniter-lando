<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EmployerProfileModel;
use App\Models\JobSeekerProfileModel;
use App\Models\PortalUserModel;
use Config\Services;

class Auth extends BaseController
{
    public function login(): string
    {
        return view('portal/auth/login', [
            'title' => lang('Portal.auth_sign_in_heading'),
        ]);
    }

    public function register(): string
    {
        return view('portal/auth/register', [
            'title' => 'Create account',
        ]);
    }

    public function attemptLogin()
    {
        if (! $this->validate('portal_login')) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email = (string) $this->request->getPost('email');
        $user  = model(PortalUserModel::class, false)->where('email', $email)->first();

        if (
            $user === null
            || ! password_verify((string) $this->request->getPost('password'), $user['password_hash'])
        ) {
            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        Services::portalAuth()->login($user['id'], $user['email'], $user['role']);

        return redirect()->to(site_url(Services::portalLocale()->localizePath('dashboard')))->with('message', 'Welcome back.');
    }

    public function attemptRegister()
    {
        if (! $this->validate('portal_register')) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $role  = (string) $this->request->getPost('role');
        $email = (string) $this->request->getPost('email');

        $db = db_connect();
        $db->transStart();

        $userModel = model(PortalUserModel::class, false);
        $userId    = $userModel->insert([
            'email'         => $email,
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role'          => $role,
        ], true);

        if (! is_numeric($userId)) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', 'Could not create account.');
        }

        $userId = (int) $userId;

        if ($role === 'employer') {
            model(EmployerProfileModel::class, false)->insert([
                'user_id'      => $userId,
                'company_name' => (string) $this->request->getPost('company_name'),
                'verified'     => 0,
            ]);
        } else {
            model(JobSeekerProfileModel::class, false)->insert([
                'user_id' => $userId,
            ]);
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Registration failed. Please try again.');
        }

        Services::portalAuth()->login($userId, $email, $role);

        return redirect()->to(site_url(Services::portalLocale()->localizePath('dashboard')))->with('message', 'Account created.');
    }

    public function logout()
    {
        Services::portalAuth()->logout();

        return redirect()->to(site_url(Services::portalLocale()->localizePath('jobs')))->with('message', 'Signed out.');
    }
}
