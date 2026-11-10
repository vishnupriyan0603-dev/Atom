<?php

namespace Tests\Unit;

use Atom\Auth\AdminWebAccess;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/config/config.php';

class AdminSessionEndpointTest extends TestCase
{
    private string $sessionScript;
    private string $testSecret;

    protected function setUp(): void
    {
        $repoRoot = dirname(__DIR__, 3);
        \Atom\Config\Config::load($repoRoot);
        $this->sessionScript = $repoRoot . '/frontend/web/admin/session.php';
        $this->testSecret = (string) \Atom\Config\Config::get('JWT_SECRET', 'test-secret');
    }

    public function testRejectsRequestWithoutToken(): void
    {
        $res = $this->invokeSessionScript('POST', [], []);
        $this->assertSame(401, $res['status']);
        $this->assertFalse($res['json']['success'] ?? true);
        $this->assertSame('A valid API sign-in token is required.', $res['json']['message'] ?? '');
    }

    public function testAcceptsTokenViaAuthorizationHeader(): void
    {
        $token = $this->createVichuToken();
        $server = ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];
        $res = $this->invokeSessionScript('POST', $server, []);
        $this->assertSame(200, $res['status']);
        $this->assertTrue($res['json']['success'] ?? false);
    }

    public function testAcceptsTokenViaXAuthorizationHeader(): void
    {
        // Simulates Apache stripping the standard Authorization header
        $token = $this->createVichuToken();
        $server = ['HTTP_X_AUTHORIZATION' => 'Bearer ' . $token];
        $res = $this->invokeSessionScript('POST', $server, []);
        $this->assertSame(200, $res['status']);
        $this->assertTrue($res['json']['success'] ?? false);
    }

    public function testAcceptsTokenViaXAdminTokenHeader(): void
    {
        $token = $this->createVichuToken();
        $server = ['HTTP_X_ADMIN_TOKEN' => $token];
        $res = $this->invokeSessionScript('POST', $server, []);
        $this->assertSame(200, $res['status']);
        $this->assertTrue($res['json']['success'] ?? false);
    }

    public function testAcceptsTokenViaJsonBody(): void
    {
        // Simulates Apache stripping headers where token is sent in JSON body
        $token = $this->createVichuToken();
        $res = $this->invokeSessionScript('POST', [], [], json_encode(['token' => $token]));
        $this->assertSame(200, $res['status']);
        $this->assertTrue($res['json']['success'] ?? false);
    }

    public function testAcceptsTokenViaPostParam(): void
    {
        $token = $this->createVichuToken();
        $res = $this->invokeSessionScript('POST', [], ['token' => $token]);
        $this->assertSame(200, $res['status']);
        $this->assertTrue($res['json']['success'] ?? false);
    }

    public function testRejectsInvalidToken(): void
    {
        $server = ['HTTP_X_AUTHORIZATION' => 'Bearer invalid.jwt.token'];
        $res = $this->invokeSessionScript('POST', $server, []);
        $this->assertSame(401, $res['status']);
        $this->assertFalse($res['json']['success'] ?? true);
        $this->assertSame('Could not verify the sign-in token for Admin access.', $res['json']['message'] ?? '');
    }

    private function createVichuToken(): string
    {
        return JWT::encode([
            'iss' => 'atom-assistant',
            'iat' => time(),
            'exp' => time() + 300,
            'user_id' => 20,
        ], $this->testSecret, 'HS256');
    }

    private function invokeSessionScript(string $method, array $serverHeaders, array $postData, ?string $rawBody = null): array
    {
        $repoRoot = dirname(__DIR__, 3);
        $cmd = 'php -d extension=sqlite3 -d extension=pdo_sqlite ' . escapeshellarg(__DIR__ . '/_session_runner.php');
        
        $input = json_encode([
            'method' => $method,
            'server' => $serverHeaders,
            'post' => $postData,
            'body' => $rawBody,
            'secret' => $this->testSecret,
        ]);

        $proc = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $repoRoot);

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($proc);

        $parsed = json_decode((string) $out, true);
        return is_array($parsed) ? $parsed : ['status' => 500, 'error' => $err, 'raw' => $out];
    }
}
