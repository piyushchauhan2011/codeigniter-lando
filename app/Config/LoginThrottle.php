<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class LoginThrottle extends BaseConfig
{
    /** POST /login attempts per minute per IP (application layer). */
    public int $maxAttemptsPerMinute = 15;
}
