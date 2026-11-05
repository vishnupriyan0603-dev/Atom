<?php

namespace App\Controllers\Api;

use App\Services\AuthService;

class Auth extends BaseApiController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register()
    {
        $raw = $this->request->getBody();
        $data = !empty($raw) ? json_decode($raw, true) : [];
        if (!is_array($data)) {
            $data = [];
        }
        $result = $this->authService->register($data);

        if (!$result['success']) {
            return $this->respondError($result['message'], 400);
        }

        return $this->respondCreated([
            'token' => $result['token'],
            'user'  => $result['user'],
        ], 'Registration successful');
    }

    public function login()
    {
        $raw = $this->request->getBody();
        $data = !empty($raw) ? json_decode($raw, true) : [];
        if (!is_array($data)) {
            $data = [];
        }
        $result = $this->authService->login($data);

        if (!$result['success']) {
            return $this->respondError($result['message'], 401);
        }

        return $this->respondSuccess([
            'token' => $result['token'],
            'user'  => $result['user'],
        ], 'Login successful');
    }

    public function me()
    {
        $user = $this->request->user;
        return $this->respondSuccess([
            'id'    => $user->id,
            'email' => $user->email,
            'name'  => $user->name,
        ]);
    }

    public function logout()
    {
        // JWT is stateless; the client discards the token. A revocation
        // blacklist can be added later if token invalidation is required.
        return $this->respondSuccess(null, 'Logged out successfully');
    }
}
