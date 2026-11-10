<?php

declare(strict_types=1);

namespace Atom\Auth;

use Atom\Config\Config;
use Firebase\JWT\Key;
use PDO;
use Throwable;

/** Verifies the API JWT used to guard server-rendered Admin pages. */
final class AdminWebAccess
{
    public const COOKIE_NAME = 'atom_admin_access';
    private const INSECURE_DEFAULT_SECRET = 'atom-assistant-jwt-secret-key-change-in-production';

    public static function decodeToken(string $token, ?string $secret = null): ?object
    {
        $secret ??= (string) Config::get('JWT_SECRET', '');
        if ($token === '' || $secret === '' || hash_equals(self::INSECURE_DEFAULT_SECRET, $secret)) {
            return null;
        }

        $autoload = dirname(__DIR__, 2) . '/backend/vendor/autoload.php';
        if (!class_exists(\Firebase\JWT\JWT::class) && is_file($autoload)) {
            require_once $autoload;
        }
        if (!class_exists(\Firebase\JWT\JWT::class)) {
            return null;
        }

        try {
            $claims = \Firebase\JWT\JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Throwable) {
            return null;
        }

        $userId = filter_var($claims->user_id ?? null, FILTER_VALIDATE_INT);
        $expiresAt = filter_var($claims->exp ?? null, FILTER_VALIDATE_INT);
        if (($claims->iss ?? null) !== 'atom-assistant' || $userId === false || $userId < 1 || $expiresAt === false || $expiresAt <= time()) {
            return null;
        }

        return $claims;
    }

    public static function isAdminRequest(array $server): bool
    {
        $path = parse_url((string) ($server['REQUEST_URI'] ?? $server['SCRIPT_NAME'] ?? ''), PHP_URL_PATH);
        return is_string($path) && preg_match('~(?:^|/)admin(?:/|$)~i', $path) === 1;
    }

    public static function adminPath(array $server): ?string
    {
        foreach (['SCRIPT_NAME', 'REQUEST_URI'] as $key) {
            $path = parse_url((string) ($server[$key] ?? ''), PHP_URL_PATH);
            if (is_string($path) && preg_match('~^(.*?/admin)(?:/|$)~i', $path, $matches) === 1) {
                return rtrim($matches[1], '/') ?: '/admin';
            }
        }

        return null;
    }

    public static function issueCookie(string $token, array $server, ?PDO $pdo = null): bool
    {
        $claims = self::decodeToken($token);
        $path = self::adminPath($server);
        if ($claims === null || $path === null || headers_sent() || !self::isSoleAdminAccount($claims, $pdo)) {
            return false;
        }

        return setcookie(self::COOKIE_NAME, $token, [
            'expires' => (int) $claims->exp,
            'path' => $path,
            'secure' => self::isHttps($server),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    public static function clearCookie(array $server): bool
    {
        $path = self::adminPath($server);
        if ($path === null || headers_sent()) {
            return false;
        }

        return setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => $path,
            'secure' => self::isHttps($server),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    public static function hasValidCookie(array $cookies, ?PDO $pdo = null, ?string $secret = null): bool
    {
        $token = $cookies[self::COOKIE_NAME] ?? '';
        $claims = is_string($token) ? self::decodeToken($token, $secret) : null;
        return $claims !== null && self::isSoleAdminAccount($claims, $pdo);
    }

    /** Enforce authentication before an Admin page or legacy action is handled. */
    public static function enforceRequest(array $server, array $query, array $cookies, ?PDO $pdo = null): void
    {
        if (!self::isAdminRequest($server)) {
            return;
        }

        $path = parse_url((string) ($server['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
        $script = parse_url((string) ($server['SCRIPT_NAME'] ?? ''), PHP_URL_PATH) ?: '';
        if (preg_match('~/(?:login|session)(?:\.php)?/?$~i', $path) === 1 || preg_match('~/(?:login|session)\.php$~i', $script) === 1) {
            return;
        }
        if (self::hasValidCookie($cookies, $pdo)) {
            return;
        }

        http_response_code(401);
        header('Cache-Control: no-store');
        if (array_key_exists('action', $query)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Admin sign-in is required.']);
            exit;
        }

        $adminPath = self::adminPath($server) ?? '/admin';
        $requestUri = (string) ($server['REQUEST_URI'] ?? '');
        $loginUrl = $adminPath . '/login.php';
        if ($requestUri !== '') {
            $loginUrl .= '?next=' . rawurlencode($requestUri);
        }
        header('Location: ' . $loginUrl, true, 302);
        exit;
    }

    private static function isHttps(array $server): bool
    {
        return (!empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off')
            || (string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /** Admin access belongs only to the currently configured single Vichu account. */
    private static function isSoleAdminAccount(object $claims, ?PDO $pdo): bool
    {
        $userId = filter_var($claims->user_id ?? null, FILTER_VALIDATE_INT);
        if ($pdo === null || $userId === false || $userId < 1) {
            return false;
        }

        try {
            $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($count !== 1) {
                return false;
            }

            $statement = $pdo->prepare('SELECT name, email FROM users WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $userId]);
            $user = $statement->fetch(PDO::FETCH_ASSOC);
            return is_array($user)
                && strcasecmp((string) ($user['name'] ?? ''), 'Vichu') === 0
                && strcasecmp((string) ($user['email'] ?? ''), 'vichu@atom.local') === 0;
        } catch (Throwable) {
            return false;
        }
    }
}
