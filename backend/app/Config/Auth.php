<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Auth extends BaseConfig
{
    public string $jwtSecret = '';

    public int $jwtExpiry = 86400 * 30;

    public string $jwtAlgorithm = 'HS256';

    public function __construct()
    {
        parent::__construct();

        // JWT authentication is disabled until a private environment secret is configured.
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
