<?php

declare(strict_types=1);

namespace App\Auth;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Authentication\Actions\ActionInterface;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Entities\UserIdentity;
use CodeIgniter\Shield\Exceptions\LogicException;
use CodeIgniter\Shield\Exceptions\RuntimeException;
use CodeIgniter\Shield\Models\UserIdentityModel;
use CodeIgniter\Shield\Config\Services as ShieldServices;

class LearningEmailActivator implements ActionInterface
{
    private string $type = Session::ID_TYPE_EMAIL_ACTIVATE;

    public function show(): string
    {
        /** @var Session $authenticator */
        $authenticator = ShieldServices::auth()->setAuthenticator('session')->getAuthenticator();

        $user = $authenticator->getPendingUser();
        if ($user === null) {
            throw new RuntimeException('Cannot get the pending login user.');
        }

        $email = $user->email;
        if ($email === null) {
            throw new LogicException('Email activation requires a user email address.');
        }

        $identity = $this->getIdentity($user);
        $code     = $identity->secret;

        log_message(
            'info',
            '[Shield learning email verification] Email={email}; code={code}; generated_at={date}',
            ['email' => $email, 'code' => $code, 'date' => Time::now()->toDateTimeString()],
        );

        return view(config('Auth')->views['action_email_activate_show'], [
            'code' => $code,
            'user' => $user,
        ]);
    }

    public function handle(IncomingRequest $request): never
    {
        throw new PageNotFoundException();
    }

    public function verify(IncomingRequest $request): RedirectResponse|string
    {
        /** @var Session $authenticator */
        $authenticator = ShieldServices::auth()->setAuthenticator('session')->getAuthenticator();

        $user = $authenticator->getPendingUser();
        if ($user === null) {
            throw new RuntimeException('Cannot get the pending login user.');
        }

        $identity = $this->getIdentity($user);
        $token    = (string) $request->getPost('token');

        if ($identity === null || ! $authenticator->checkAction($identity, $token)) {
            return view(config('Auth')->views['action_email_activate_show'], [
                'code'  => $identity !== null ? $identity->secret : '',
                'error' => lang('Auth.invalidActivateToken'),
                'user'  => $user,
            ]);
        }

        $user = $authenticator->getUser();
        if ($user === null) {
            throw new RuntimeException('Cannot complete activation without a user.');
        }

        $user->activate();

        return redirect()->to(config('Auth')->registerRedirect())
            ->with('message', lang('Auth.registerSuccess'));
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function createIdentity(User $user): string
    {
        /** @var UserIdentityModel $identityModel */
        $identityModel = model(UserIdentityModel::class);
        $identityModel->deleteIdentitiesByType($user, $this->type);

        return $identityModel->createCodeIdentity(
            $user,
            [
                'type'  => $this->type,
                'name'  => 'register',
                'extra' => lang('Auth.needVerification'),
            ],
            static fn (): string => (string) random_int(100000, 999999),
        );
    }

    private function getIdentity(User $user): ?UserIdentity
    {
        /** @var UserIdentityModel $identityModel */
        $identityModel = model(UserIdentityModel::class);

        return $identityModel->getIdentityByType($user, $this->type);
    }
}
