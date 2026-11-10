<?php

namespace App\Services;

use App\Models\UserModel;
use Config\Auth as AuthConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    private ?UserModel $userModel = null;
    private AuthConfig $authConfig;

    public function __construct()
    {
        $this->authConfig = new AuthConfig();
    }

    private function getUserModel(): ?UserModel
    {
        if ($this->userModel === null) {
            try {
                $this->userModel = new UserModel();
            } catch (\Throwable $e) {
                return null;
            }
        }
        return $this->userModel;
    }

    public function register(array $data): array
    {
        // ATOM Admin is configured as a single-user account; public sign-up
        // would grant any new account access to the Admin surface.
        return ['success' => false, 'message' => 'Account registration is disabled'];
    }

    public function login(array $data): array
    {
        $identifier = trim((string) ($data['username'] ?? ''));
        if ($identifier === '') {
            // Keep compatibility with existing Web/Mobile clients that send `email`.
            $identifier = trim((string) ($data['email'] ?? ''));
        }
        $password = (string) ($data['password'] ?? '');

        if ($identifier === '' || $password === '') {
            return ['success' => false, 'message' => 'Username or email and password are required'];
        }

        if (!$this->hasConfiguredJwtSecret()) {
            return ['success' => false, 'message' => 'Authentication is temporarily unavailable'];
        }
        $model = $this->getUserModel();
        if ($model === null) {
            return ['success' => false, 'message' => 'Authentication is temporarily unavailable'];
        }

        try {
            $user = $model->where('email', $identifier)->first();
            if (!$user) {
                $user = $model->where('name', $identifier)->first();
            }
            if ($user && password_verify($password, $user->password)) {
                $token = $this->generateToken((int) $user->id);
                return [
                    'success' => true,
                    'token'   => $token,
                    'user'    => ['id' => $user->id, 'email' => $user->email, 'name' => $user->name],
                ];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Authentication is temporarily unavailable'];
        }

        return ['success' => false, 'message' => 'Invalid username/email or password'];
    }

    public function validateToken(string $token): ?object
    {
        if (!$this->hasConfiguredJwtSecret()) {
            return null;
        }
        try {
            return JWT::decode($token, new Key($this->authConfig->jwtSecret, $this->authConfig->jwtAlgorithm));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserFromToken(string $token): ?object
    {
        $decoded = $this->validateToken($token);
        if (!$decoded || !isset($decoded->user_id) || (int) $decoded->user_id < 1) {
            return null;
        }
        $model = $this->getUserModel();
        if ($model === null) {
            return null;
        }
        try {
            return $model->find($decoded->user_id) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function generateToken(int $userId): string
    {
        if ($userId < 1 || !$this->hasConfiguredJwtSecret()) {
            throw new \RuntimeException('Authentication is not configured');
        }
        $payload = [
            'iss'    => 'atom-assistant',
            'iat'    => time(),
            'exp'    => time() + $this->authConfig->jwtExpiry,
            'user_id' => $userId,
        ];
        return JWT::encode($payload, $this->authConfig->jwtSecret, $this->authConfig->jwtAlgorithm);
    }

    private function hasConfiguredJwtSecret(): bool
    {
        $secret = trim((string) $this->authConfig->jwtSecret);
        return $secret !== ''
            && $secret !== 'atom-assistant-jwt-secret-key-change-in-production';
    }
}
