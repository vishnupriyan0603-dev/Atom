<?php

namespace App\Services;

use App\Models\UserModel;
use Config\Auth as AuthConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    private UserModel $userModel;
    private AuthConfig $authConfig;

    public function __construct()
    {
        $this->userModel  = new UserModel();
        $this->authConfig = new AuthConfig();
    }

    public function register(array $data): array
    {
        if (empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $existing = $this->userModel->where('email', $data['email'])->first();
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        $userId = $this->userModel->insert([
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'name'     => $data['name'] ?? '',
        ]);

        $token = $this->generateToken($userId);

        return [
            'success' => true,
            'token'   => $token,
            'user'    => ['id' => $userId, 'email' => $data['email'], 'name' => $data['name'] ?? ''],
        ];
    }

    public function login(array $data): array
    {
        if (empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $user = $this->userModel->where('email', $data['email'])->first();
        if (!$user || !password_verify($data['password'], $user->password)) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        $token = $this->generateToken($user->id);

        return [
            'success' => true,
            'token'   => $token,
            'user'    => ['id' => $user->id, 'email' => $user->email, 'name' => $user->name],
        ];
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
        return $this->userModel->find($decoded->user_id);
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
