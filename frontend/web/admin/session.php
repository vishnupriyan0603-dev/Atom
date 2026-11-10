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

/**
 * Extract the Bearer JWT token across multiple server and proxy environments:
 * 1. $_SERVER HTTP authorization headers (including Apache mod_rewrite REDIRECT_)
 * 2. Custom headers (X-Authorization, X-Admin-Token) which Apache never strips
 * 3. apache_request_headers() if running under Apache FastCGI/mod_php
 * 4. JSON request payload { "token": "..." }
 * 5. URL-encoded or multipart form POST parameter "token"
 */
function extractBearerToken(): string {
    $headerCandidates = [
        $_SERVER['HTTP_AUTHORIZATION'] ?? null,
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        $_SERVER['HTTP_X_AUTHORIZATION'] ?? null,
        $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? null,
    ];

    foreach ($headerCandidates as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '') {
            $val = trim($candidate);
            if (preg_match('/^Bearer\s+(.+)$/i', $val, $matches)) {
                return trim($matches[1]);
            }
            if (!str_contains($val, ' ')) {
                return $val;
            }
        }
    }

    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $key => $val) {
            $lower = strtolower((string) $key);
            if (in_array($lower, ['authorization', 'x-authorization', 'x-admin-token'], true) && is_string($val)) {
                $val = trim($val);
                if (preg_match('/^Bearer\s+(.+)$/i', $val, $matches)) {
                    return trim($matches[1]);
                }
                if (!str_contains($val, ' ')) {
                    return $val;
                }
            }
        }
    }

    $rawBody = (string) ($GLOBALS['atomAdminSessionRawBody'] ?? file_get_contents('php://input'));
    if ($rawBody !== '') {
        $json = json_decode($rawBody, true);
        if (is_array($json)) {
            $candidate = $json['token'] ?? $json['access_token'] ?? null;
            if (is_string($candidate) && trim($candidate) !== '') {
                $val = trim($candidate);
                if (preg_match('/^Bearer\s+(.+)$/i', $val, $matches)) {
                    return trim($matches[1]);
                }
                return $val;
            }
        }
    }

    if (!empty($_POST['token']) && is_string($_POST['token'])) {
        $val = trim($_POST['token']);
        if (preg_match('/^Bearer\s+(.+)$/i', $val, $matches)) {
            return trim($matches[1]);
        }
        return $val;
    }

    return '';
}

$token = extractBearerToken();
if ($token === '') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'A valid API sign-in token is required.']);
    exit;
}

$pdo = ($dbConnected && $dbConnection !== null) ? $dbConnection->getPdo() : ($GLOBALS['atomAdminAuthTestPdo'] ?? null);
if (!AdminWebAccess::issueCookie($token, $_SERVER, $pdo)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Could not verify the sign-in token for Admin access.']);
    exit;
}

echo json_encode(['success' => true]);
