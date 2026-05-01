<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Session-backed authentication for the job portal module.
 */
class PortalAuth
{
    public const SESSION_USER_ID = 'portal_user_id';

    public const SESSION_EMAIL = 'portal_email';

    public const SESSION_ROLE = 'portal_role';

    public function __construct(private readonly object $session)
    {
    }

    public function id(): ?int
    {
        $id = $this->session->get(self::SESSION_USER_ID);

        return $id !== null ? (int) $id : null;
    }

    public function email(): ?string
    {
        $email = $this->session->get(self::SESSION_EMAIL);

        return $email !== null ? (string) $email : null;
    }

    public function role(): ?string
    {
        $role = $this->session->get(self::SESSION_ROLE);

        return $role !== null ? (string) $role : null;
    }

    public function check(): bool
    {
        return $this->id() !== null && $this->role() !== null;
    }

    /**
     * @param int|string $userId Row IDs from the DB layer are often strings (PDO string mode).
     */
    public function login(int|string $userId, string $email, string $role): void
    {
        $this->session->set([
            self::SESSION_USER_ID => (int) $userId,
            self::SESSION_EMAIL   => $email,
            self::SESSION_ROLE    => $role,
        ]);
    }

    public function logout(): void
    {
        $this->session->remove([self::SESSION_USER_ID, self::SESSION_EMAIL, self::SESSION_ROLE]);
    }

    public function isEmployer(): bool
    {
        return $this->role() === 'employer';
    }

    public function isSeeker(): bool
    {
        return $this->role() === 'seeker';
    }
}
