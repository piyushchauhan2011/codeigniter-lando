<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Shield\Config\Services as ShieldServices;

/**
 * Thin portal-facing adapter around Shield's current user.
 */
class PortalAuth
{
    public function id(): ?int
    {
        $id = ShieldServices::auth()->id();

        return $id !== null ? (int) $id : null;
    }

    public function email(): ?string
    {
        $email = ShieldServices::auth()->user()?->email;

        return $email;
    }

    public function role(): ?string
    {
        $user = ShieldServices::auth()->user();
        if ($user === null) {
            return null;
        }

        foreach (['admin', 'employer', 'seeker'] as $group) {
            if ($user->inGroup($group)) {
                return $group;
            }
        }

        return null;
    }

    public function check(): bool
    {
        return ShieldServices::auth()->loggedIn();
    }

    public function isEmployer(): bool
    {
        return $this->role() === 'employer';
    }

    public function isSeeker(): bool
    {
        return $this->role() === 'seeker';
    }

    public function isAdmin(): bool
    {
        return $this->role() === 'admin';
    }
}
