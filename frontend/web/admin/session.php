<?php
require_once __DIR__ . '/../bootstrap.php';

use Atom\Auth\AdminWebAccess;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'DELETE') {
    if (!AdminWebAccess::clearCookie($_SERVER)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not clear the Admin session cookie.']);
        exit;
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($method !== 'POST') {
    header('Allow: POST, DELETE');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST to sign in or DELETE to sign out.']);
    exit;
}

$authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if (!str_starts_with($authorization, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'A valid API sign-in token is required.']);
    exit;
}

$token = substr($authorization, 7);
if (!AdminWebAccess::issueCookie($token, $_SERVER, $dbConnected ? $dbConnection->getPdo() : null)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Could not verify the sign-in token for Admin access.']);
    exit;
}

echo json_encode(['success' => true]);
