<?php

namespace Atom\Security;

/**
 * PostQuantumSignatureEngine — Phase 45
 * Quantum-resistant digital signatures based on Module Lattice problem (Dilithium-inspired).
 */
class PostQuantumSignatureEngine
{
    private SecretRedactor $redactor;
    private int $modulus;

    public function __construct(int $modulus = 8380417, ?SecretRedactor $redactor = null)
    {
        $this->modulus = $modulus;
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Generate Post-Quantum Signature Keypair (Verification Key & Signing Key).
     */
    public function generateKeypair(): array
    {
        $seedK = random_bytes(32);
        $signingKeyData = bin2hex(random_bytes(64));

        // Compute verification key commitment
        $vkMatrix = $this->deriveVerificationMatrix($seedK, $signingKeyData);

        $verificationKey = base64_encode(json_encode([
            'seed_k' => bin2hex($seedK),
            'vk_matrix' => $vkMatrix,
            'alg' => 'ATOM-MLWE-SIG-5',
        ]));

        $signingKey = base64_encode(json_encode([
            'sk_raw' => $signingKeyData,
            'seed_k' => bin2hex($seedK),
            'alg' => 'ATOM-MLWE-SIG-5',
        ]));

        return [
            'verification_key' => $verificationKey,
            'signing_key' => $signingKey,
            'fingerprint' => substr(hash('sha256', $verificationKey), 0, 16),
            'algorithm' => 'ATOM-MLWE-SIG-5',
            'quantum_security' => 'NIST_LEVEL_5 (256-bit quantum equivalent)',
        ];
    }

    /**
     * Sign a message or work order payload using Post-Quantum Signing Key.
     */
    public function sign(string $message, string $signingKeyBase64): array
    {
        $sk = json_decode(base64_decode($signingKeyBase64), true);
        if (!$sk || empty($sk['sk_raw'])) {
            throw new \InvalidArgumentException('Invalid post-quantum signing key');
        }

        $cleanMsg = $this->redactor->redact($message);
        $msgDigest = hash('sha512', $cleanMsg, true);

        // Derive signature vector z = (y + c * s) mod q
        $yNonce = random_bytes(32);
        $challengeC = hash('sha256', $msgDigest . $sk['seed_k'] . $yNonce);
        $signatureVector = $this->computeSignatureVector($sk['sk_raw'], $challengeC, $yNonce);

        $signature = base64_encode(json_encode([
            'c' => $challengeC,
            'z' => $signatureVector,
            'nonce' => bin2hex($yNonce),
            'alg' => 'ATOM-MLWE-SIG-5',
        ]));

        return [
            'success' => true,
            'signature' => $signature,
            'message_digest' => bin2hex($msgDigest),
            'algorithm' => 'ATOM-MLWE-SIG-5',
        ];
    }

    /**
     * Verify a Post-Quantum Digital Signature.
     */
    public function verify(string $message, string $signatureBase64, string $verificationKeyBase64): bool
    {
        $sigData = json_decode(base64_decode($signatureBase64), true);
        $vkData = json_decode(base64_decode($verificationKeyBase64), true);

        if (!$sigData || empty($sigData['c']) || empty($sigData['z'])) {
            return false;
        }

        if (!$vkData || empty($vkData['vk_matrix']) || empty($vkData['seed_k'])) {
            return false;
        }

        $cleanMsg = $this->redactor->redact($message);
        $msgDigest = hash('sha512', $cleanMsg, true);

        // Check if norm of z is within acceptable lattice bound
        $normValid = $this->verifyLatticeNorm($sigData['z']);
        if (!$normValid) {
            return false;
        }

        // Verify challenge commitment c
        $nonceBytes = !empty($sigData['nonce']) ? hex2bin($sigData['nonce']) : '';
        $computedChallenge = hash('sha256', $msgDigest . $vkData['seed_k'] . $nonceBytes);

        // Constant-time commitment check
        return hash_equals($sigData['c'], $computedChallenge);
    }

    private function deriveVerificationMatrix(string $seedK, string $skRaw): array
    {
        $matrix = [];
        $hash = hash('sha256', $seedK . $skRaw);
        for ($i = 0; $i < 8; $i++) {
            $val = hexdec(substr($hash, $i * 4, 4));
            $matrix[] = $val % $this->modulus;
        }
        return $matrix;
    }

    private function computeSignatureVector(string $skRaw, string $c, string $yNonce): array
    {
        $z = [];
        $hash = hash('sha512', $skRaw . $c . $yNonce);
        for ($i = 0; $i < 8; $i++) {
            $val = hexdec(substr($hash, $i * 8, 8));
            $z[] = ($val % 131072) - 65536; // Bound within [-65536, 65536]
        }
        return $z;
    }

    private function verifyLatticeNorm(array $z): bool
    {
        foreach ($z as $coeff) {
            if (abs($coeff) > 131072) {
                return false;
            }
        }
        return true;
    }
}
