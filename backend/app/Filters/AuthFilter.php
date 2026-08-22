<?php

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $token = $request->getHeaderLine('Authorization');

        if (empty($token) || !str_starts_with($token, 'Bearer ')) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON(['success' => false, 'message' => 'Missing or invalid authorization header']);
        }

        $token     = substr($token, 7);
        $authService = new AuthService();
        $user      = $authService->getUserFromToken($token);

        if (!$user) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON(['success' => false, 'message' => 'Invalid or expired token']);
        }

        $request->user = $user;
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->setHeader('Access-Control-Max-Age', '86400');
        
        if ($request->getMethod() === 'OPTIONS') {
            return $response->setStatusCode(ResponseInterface::HTTP_NO_CONTENT);
        }
        
        return $response;
    }
}
