<?php

declare(strict_types=1);

namespace App\Validation;

use CodeIgniter\Validation\StrictRules\Rules;

/**
 * Rules for portal registration (CodeIgniter has no Laravel-style required_if).
 */
class PortalUserRules extends Rules
{
    /**
     * Employer registrations require company name; seekers may omit it.
     * If company name is present, length limits apply to both roles.
     *
     * @param mixed $value
     */
    public function company_name_for_registration(
        $value,
        ?string $param,
        array $data,
        ?string &$error = null,
        ?string $field = null,
    ): bool {
        $role = (string) ($data['role'] ?? '');
        $v    = $value === null ? '' : trim((string) $value);

        if ($role === 'employer') {
            if ($v === '') {
                $error = 'The Company name field is required when registering as an employer.';

                return false;
            }

            return $this->checkCompanyNameLength($v, $error);
        }

        if ($v !== '') {
            return $this->checkCompanyNameLength($v, $error);
        }

        return true;
    }

    private function checkCompanyNameLength(string $v, ?string &$error): bool
    {
        if (strlen($v) < 2) {
            $error = 'The Company name field must be at least 2 characters in length.';

            return false;
        }

        if (strlen($v) > 160) {
            $error = 'The Company name field cannot exceed 160 characters in length.';

            return false;
        }

        return true;
    }
}
