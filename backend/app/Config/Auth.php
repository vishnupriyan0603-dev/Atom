<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Auth extends BaseConfig
{
    public string $jwtSecret = 'atom-assistant-jwt-secret-key-change-in-production';

    public int $jwtExpiry = 86400 * 30;

    public string $jwtAlgorithm = 'HS256';

    public function __construct()
    {
        parent::__construct();

        // Prefer an environment-provided secret; fall back to the local default.
        $envSecret = env('JWT_SECRET', '');
        if ($envSecret !== '') {
            $this->jwtSecret = $envSecret;
        }

        $envExpiry = env('JWT_EXPIRY', '');
        if ($envExpiry !== '' && is_numeric($envExpiry)) {
            $this->jwtExpiry = (int) $envExpiry;
        }
    }
}
