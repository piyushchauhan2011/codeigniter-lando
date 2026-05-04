<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Api extends BaseConfig
{
    /**
     * Sliding-window-ish requests per minute per IP for /api/v1/* (application layer).
     * nginx should apply edge limits separately (see ops/lxc/nginx-reverse-proxy-example.conf).
     */
    public int $throttlePerMinute = 120;
}
