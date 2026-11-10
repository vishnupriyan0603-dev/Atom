<?php

namespace Tests\Unit;

use Atom\Auth\AdminWebAccess;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/src/Auth/AdminWebAccess.php';

class AdminWebAccessTest extends TestCase
{
    private const TEST_SECRET = 'test-only-admin-web-access-secret';

    public function testAcceptsValidApiStyleToken(): void
    {
        $token = $this->token();

        $claims = AdminWebAccess::decodeToken($token, self::TEST_SECRET);

        $this->assertNotNull($claims);
        $this->assertSame(27, $claims->user_id);
    }

    public function testRejectsInvalidSignatureAndMalformedToken(): void
    {
        $token = $this->token();

        $this->assertNull(AdminWebAccess::decodeToken($token, 'a-different-test-secret'));
        $this->assertNull(AdminWebAccess::decodeToken('not-a-jwt', self::TEST_SECRET));
    }

    public function testRejectsExpiredToken(): void
    {
        $token = $this->token(['exp' => time() - 1]);

        $this->assertNull(AdminWebAccess::decodeToken($token, self::TEST_SECRET));
    }

    public function testRejectsUnexpectedIssuerAndInvalidUserId(): void
    {
        $wrongIssuer = $this->token(['iss' => 'other-app']);
        $invalidUser = $this->token(['user_id' => 0]);

        $this->assertNull(AdminWebAccess::decodeToken($wrongIssuer, self::TEST_SECRET));
        $this->assertNull(AdminWebAccess::decodeToken($invalidUser, self::TEST_SECRET));
    }

    public function testRejectsKnownInsecureDefaultSecret(): void
    {
        $defaultSecret = 'atom-assistant-jwt-secret-key-change-in-production';
        $token = JWT::encode([
            'iss' => 'atom-assistant',
            'iat' => time(),
            'exp' => time() + 60,
            'user_id' => 27,
        ], $defaultSecret, 'HS256');

        $this->assertNull(AdminWebAccess::decodeToken($token, $defaultSecret));
    }

    public function testCookieRequiresTheOnlyConfiguredVichuAccount(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');
        $pdo->exec("INSERT INTO users (id, name, email) VALUES (20, 'Vichu', 'vichu@atom.local')");
        $cookie = [AdminWebAccess::COOKIE_NAME => $this->token(['user_id' => 20])];

        $this->assertTrue(AdminWebAccess::hasValidCookie($cookie, $pdo, self::TEST_SECRET));
        $this->assertFalse(AdminWebAccess::hasValidCookie([
            AdminWebAccess::COOKIE_NAME => $this->token(['user_id' => 21]),
        ], $pdo, self::TEST_SECRET));

        $pdo->exec("INSERT INTO users (id, name, email) VALUES (21, 'Other', 'other@example.invalid')");
        $this->assertFalse(AdminWebAccess::hasValidCookie($cookie, $pdo, self::TEST_SECRET));
    }

    public function testFindsAdminRequestsAndScopesCookieToAdminDirectory(): void
    {
        $server = [
            'SCRIPT_NAME' => '/my%20work/Atom/frontend/web/admin/session.php',
            'REQUEST_URI' => '/my%20work/Atom/frontend/web/admin/session.php',
        ];

        $this->assertTrue(AdminWebAccess::isAdminRequest($server));
        $this->assertSame('/my%20work/Atom/frontend/web/admin', AdminWebAccess::adminPath($server));
        $this->assertFalse(AdminWebAccess::isAdminRequest(['REQUEST_URI' => '/my%20work/Atom/frontend/web/chat.php']));
    }

    private function token(array $overrides = []): string
    {
        return JWT::encode(array_merge([
            'iss' => 'atom-assistant',
            'iat' => time(),
            'exp' => time() + 60,
            'user_id' => 27,
        ], $overrides), self::TEST_SECRET, 'HS256');
    }
}
