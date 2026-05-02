<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EmployerProfileModel;
use App\Models\JobSeekerProfileModel;
use CodeIgniter\Events\Events;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Config\Services as ShieldServices;
use CodeIgniter\Shield\Exceptions\ValidationException;
use CodeIgniter\Shield\Models\UserModel;
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

        /** @var Session $authenticator */
        $authenticator = ShieldServices::auth()->setAuthenticator('session')->getAuthenticator();
        $result        = $authenticator->remember((bool) $this->request->getPost('remember'))->attempt([
            'email'    => (string) $this->request->getPost('email'),
            'password' => (string) $this->request->getPost('password'),
        ]);

        if (! $result->isOK()) {
            return redirect()->back()->withInput()->with('error', $result->reason());
        }

        if ($authenticator->hasAction()) {
            return redirect()->route('auth-action-show')->withCookies();
        }

        return redirect()
            ->to(site_url(Services::portalLocale()->localizePath('dashboard')))
            ->with('message', 'Welcome back.')
            ->withCookies();
    }

    public function attemptRegister()
    {
        if (! $this->validate('portal_register')) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $role  = (string) $this->request->getPost('role');
        $email = (string) $this->request->getPost('email');

        /** @var UserModel $userModel */
        $userModel = model(UserModel::class, false);
        $user      = $userModel->createNewUser([
            'active'   => 0,
            'email'    => $email,
            'password' => (string) $this->request->getPost('password'),
        ]);

        $authDbGroup = config('Auth')->DBGroup;
        $db          = $authDbGroup !== null ? db_connect($authDbGroup) : db_connect();
        $db->transStart();

        try {
            $userModel->save($user);
        } catch (ValidationException) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('errors', $userModel->errors());
        }

        $user = $userModel->findById($userModel->getInsertID());
        if ($user === null) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', 'Could not create account.');
        }

        $user->addGroup($role);
        $userId = (int) $user->id;

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

        Events::trigger('register', $user);

        /** @var Session $authenticator */
        $authenticator = ShieldServices::auth()->setAuthenticator('session')->getAuthenticator();
        $authenticator->startLogin($user);

        if ($authenticator->startUpAction('register', $user)) {
            return redirect()->route('auth-action-show');
        }

        $user->activate();
        $authenticator->completeLogin($user);

        return redirect()->to(site_url(Services::portalLocale()->localizePath('dashboard')))->with('message', 'Account created.');
    }

    public function logout(): RedirectResponse
    {
        ShieldServices::auth()->logout();

        return redirect()->to(site_url(Services::portalLocale()->localizePath('jobs')))->with('message', 'Signed out.');
    }
}
