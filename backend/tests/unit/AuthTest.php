<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuthTest extends CIUnitTestCase
{
    public function testPasswordHashVerification(): void
    {
        $password = 'SecretAtomPass123!';
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

        $this->assertNotEmpty($hash);
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('WrongPassword', $hash));
    }

    public function testInputSanitizationAndEscaping(): void
    {
        $maliciousInput = "<script>alert('xss')</script>";
        $escaped = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        $this->assertEquals("&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;", $escaped);
        $this->assertStringNotContainsString("<script>", $escaped);
    }

    public function testBearerTokenExtraction(): void
    {
        $authHeader = "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.e30.secret";
        
        $token = null;
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }

        $this->assertEquals("eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.e30.secret", $token);
    }

    public function testInvalidBearerTokenRejection(): void
    {
        $invalidHeader = "Basic dXNlcjpwYXNz";
        
        $token = null;
        if (preg_match('/Bearer\s+(.*)$/i', $invalidHeader, $matches)) {
            $token = $matches[1];
        }

        $this->assertNull($token);
    }

    public function testAuthenticationFailsClosedWithoutJwtSecret(): void
    {
        $service = new \App\Services\AuthService();
        $config = new \Config\Auth();
        $config->jwtSecret = '';
        $property = new \ReflectionProperty($service, 'authConfig');
        $property->setAccessible(true);
        $property->setValue($service, $config);

        $login = $service->login(['username' => 'vichu', 'password' => 'not-logged']);
        $register = $service->register(['email' => 'audit@example.invalid', 'password' => 'not-logged']);

        $this->assertFalse($login['success']);
        $this->assertArrayNotHasKey('token', $login);
        $this->assertFalse($register['success']);
        $this->assertArrayNotHasKey('token', $register);
        $this->assertNull($service->validateToken('not-a-valid-token'));
    }
}
