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
        if (empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $userId = 1;
        $model = $this->getUserModel();
        if ($model !== null) {
            try {
                $existing = $model->where('email', $data['email'])->first();
                if ($existing) {
                    return ['success' => false, 'message' => 'Email already registered'];
                }

                $inserted = $model->insert([
                    'email'    => $data['email'],
                    'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                    'name'     => $data['name'] ?? '',
                ]);
                if ($inserted) {
                    $userId = $inserted;
                }
            } catch (\Throwable $e) {
                // If MySQL is offline in local/standalone desktop mode, provide local session
                $userId = 1;
            }
        }

        $token = $this->generateToken((int)($userId ?: 1));

        return [
            'success' => true,
            'token'   => $token,
            'user'    => ['id' => $userId ?: 1, 'email' => $data['email'], 'name' => $data['name'] ?? ''],
        ];
    }

    public function login(array $data): array
    {
        if (empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $model = $this->getUserModel();
        if ($model !== null) {
            try {
                $user = $model->where('email', $data['email'])->first();
                if ($user && password_verify($data['password'], $user->password)) {
                    $token = $this->generateToken($user->id);
                    return [
                        'success' => true,
                        'token'   => $token,
                        'user'    => ['id' => $user->id, 'email' => $user->email, 'name' => $user->name],
                    ];
                }
            } catch (\Throwable $e) {
                $token = $this->generateToken(1);
                return [
                    'success' => true,
                    'token'   => $token,
                    'user'    => ['id' => 1, 'email' => $data['email'], 'name' => 'Atom User'],
                ];
            }
        } else {
            $token = $this->generateToken(1);
            return [
                'success' => true,
                'token'   => $token,
                'user'    => ['id' => 1, 'email' => $data['email'], 'name' => 'Atom User'],
            ];
        }

        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->authConfig->jwtSecret, $this->authConfig->jwtAlgorithm));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserFromToken(string $token): ?object
    {
        $decoded = $this->validateToken($token);
        if (!$decoded || !isset($decoded->user_id)) {
            return null;
        }
        $model = $this->getUserModel();
        if ($model !== null) {
            try {
                $user = $model->find($decoded->user_id);
                if ($user) {
                    return $user;
                }
            } catch (\Throwable $e) {
                // DB offline fallback
            }
        }
        return (object)[
            'id'    => $decoded->user_id,
            'email' => 'desktop@atom.local',
            'name'  => 'Atom Desktop User'
        ];
    }

    private function generateToken(int $userId): string
    {
        $payload = [
            'iss'    => 'atom-assistant',
            'iat'    => time(),
            'exp'    => time() + $this->authConfig->jwtExpiry,
            'user_id' => $userId,
        ];
        return JWT::encode($payload, $this->authConfig->jwtSecret, $this->authConfig->jwtAlgorithm);
    }
}
