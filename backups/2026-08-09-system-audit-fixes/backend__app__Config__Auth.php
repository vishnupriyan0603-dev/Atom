<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Auth extends BaseConfig
{
    public string $jwtSecret = 'atom-assistant-jwt-secret-key-change-in-production';

    public int $jwtExpiry = 86400 * 30;

    public string $jwtAlgorithm = 'HS256';
}
