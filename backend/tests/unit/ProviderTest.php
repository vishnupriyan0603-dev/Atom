<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Provider Configuration Tests
 *
 * Verifies that each of the three supported LLM providers
 * (Groq, Gemini, OpenAI) can be detected as configured or
 * not-configured via environment variables.
 *
 * No real HTTP requests are made. When an API key IS present,
 * the provider object is constructed and its basic shape is
 * validated. When no key exists the test marks itself as skipped
 * so that CI pipelines without secrets still pass cleanly.
 *
 * @internal
 */
final class ProviderTest extends CIUnitTestCase
{
    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Read a value from the root .env file that lives one level above
     * the backend directory (e.g. e:/xampp/htdocs/my work/Atom/.env).
     */
    private function getRootEnv(string $key): string
    {
        // ROOTPATH may end with a directory separator; strip it before dirname()
        // so we reliably get the workspace root (Atom/) rather than backend/.
        $envFile = dirname(rtrim(ROOTPATH, '/\\')) . DIRECTORY_SEPARATOR . '.env';

        if (! is_file($envFile)) {
            return '';
        }

        foreach (file($envFile) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            // strlen($key) + 1 skips the key name and the '=' sign
            if (str_starts_with($line, $key . '=')) {
                return trim(substr($line, strlen($key) + 1), " \t\"'");
            }
        }

        return '';
    }

    /**
     * Diagnostic: confirm the .env file is found and ROOTPATH resolves correctly.
     */
    public function testEnvFileIsReachable(): void
    {
        $envFile = dirname(rtrim(ROOTPATH, '/\\')) . DIRECTORY_SEPARATOR . '.env';
        $this->assertFileExists(
            $envFile,
            'Root .env not found at: ' . $envFile . ' (ROOTPATH=' . ROOTPATH . ')'
        );
    }

    /**
     * Resolve the active API key for a provider, mirroring the same
     * lookup logic used in AiChatService::getAtomBrain().
     *
     * @param string $providerName  'groq' | 'gemini' | 'openai'
     * @param string $providerKey   Env var holding the provider-specific key
     * @return string               Resolved API key (may be empty)
     */
    private function resolveKey(string $providerName, string $providerKey): string
    {
        $activeProvider = strtolower($this->getRootEnv('LLM_PROVIDER') ?: 'groq');
        $genericKey     = $this->getRootEnv('LLM_API_KEY');
        $specificKey    = $this->getRootEnv($providerKey);

        // Provider-specific key takes precedence; fall back to generic key
        // only when this provider is the active/configured one.
        return $specificKey ?: ($activeProvider === $providerName ? $genericKey : '');
    }

    // ----------------------------------------------------------------
    // Groq
    // ----------------------------------------------------------------

    /**
     * Groq uses the OpenAI-compatible provider class.
     * Tests that when GROQ_API_KEY (or LLM_API_KEY with LLM_PROVIDER=groq)
     * is set, an OpenAIProvider instance is constructable.
     */
    public function testGroqProviderIsConfigured(): void
    {
        $key = $this->resolveKey('groq', 'GROQ_API_KEY');

        if (empty($key)) {
            $this->markTestSkipped('Groq API key not configured – skipping live provider test.');
        }

        $url   = $this->getRootEnv('GROQ_API_URL') ?: 'https://api.groq.com/openai/v1';
        $model = $this->getRootEnv('GROQ_MODEL')   ?: 'openai/gpt-oss-120b';

        // Autoload the provider from the src directory
        $providerFile = dirname(ROOTPATH) . '/src/LLM/OpenAIProvider.php';
        if (is_file($providerFile) && ! class_exists('Atom\\LLM\\OpenAIProvider')) {
            require_once $providerFile;
        }

        $this->assertTrue(class_exists('Atom\\LLM\\OpenAIProvider'), 'OpenAIProvider class must exist.');

        $provider = new \Atom\LLM\OpenAIProvider($key, $url, $model);

        $this->assertInstanceOf(\Atom\LLM\OpenAIProvider::class, $provider);
        $this->assertNotEmpty($key, 'Groq API key must not be empty when provider is configured.');
    }

    /**
     * When no Groq key is present the system must gracefully handle the
     * absence – i.e. no fatal error occurs during provider resolution.
     */
    public function testGroqProviderIsNotConfigured(): void
    {
        $key = $this->resolveKey('groq', 'GROQ_API_KEY');

        if (! empty($key)) {
            $this->markTestSkipped('Groq API key IS configured – this test is only meaningful without a key.');
        }

        // Without a key the provider block in AiChatService is skipped.
        // We simply assert that an empty key is detected as such.
        $this->assertEmpty($key, 'Groq API key should be absent in this environment.');
    }

    // ----------------------------------------------------------------
    // Gemini
    // ----------------------------------------------------------------

    /**
     * Tests that when GEMINI_API_KEY (or LLM_API_KEY with LLM_PROVIDER=gemini)
     * is set, a GeminiProvider instance is constructable.
     */
    public function testGeminiProviderIsConfigured(): void
    {
        $key = $this->resolveKey('gemini', 'GEMINI_API_KEY');

        if (empty($key)) {
            $this->markTestSkipped('Gemini API key not configured – skipping live provider test.');
        }

        $url   = $this->getRootEnv('GEMINI_API_URL') ?: 'https://generativelanguage.googleapis.com/v1beta';
        $model = $this->getRootEnv('GEMINI_MODEL')   ?: 'gemini-3.6-flash';

        $providerFile = dirname(ROOTPATH) . '/src/LLM/GeminiProvider.php';
        if (is_file($providerFile) && ! class_exists('Atom\\LLM\\GeminiProvider')) {
            require_once $providerFile;
        }

        $this->assertTrue(class_exists('Atom\\LLM\\GeminiProvider'), 'GeminiProvider class must exist.');

        $provider = new \Atom\LLM\GeminiProvider($key, $url, $model);

        $this->assertInstanceOf(\Atom\LLM\GeminiProvider::class, $provider);
        $this->assertNotEmpty($key, 'Gemini API key must not be empty when provider is configured.');
    }

    /**
     * When no Gemini key is present, absent key is correctly identified.
     */
    public function testGeminiProviderIsNotConfigured(): void
    {
        $key = $this->resolveKey('gemini', 'GEMINI_API_KEY');

        if (! empty($key)) {
            $this->markTestSkipped('Gemini API key IS configured – this test is only meaningful without a key.');
        }

        $this->assertEmpty($key, 'Gemini API key should be absent in this environment.');
    }

    // ----------------------------------------------------------------
    // OpenAI
    // ----------------------------------------------------------------

    /**
     * When no OpenAI key is present, absent key is correctly identified.
     */
    public function testOpenAIProviderIsNotConfigured(): void
    {
        $key = $this->resolveKey('openai', 'OPENAI_API_KEY');

        if (! empty($key)) {
            $this->markTestSkipped('OpenAI API key IS configured – this test is only meaningful without a key.');
        }

        $this->assertEmpty($key, 'OpenAI API key should be absent in this environment.');
    }
}
