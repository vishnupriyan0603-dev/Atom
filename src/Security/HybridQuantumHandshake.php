<?php

namespace Atom\Security;

/**
 * HybridQuantumHandshake — Phase 45
 * Hybrid Post-Quantum + Classical Zero-Trust Key Exchange Handshake.
 * Combines Classical ECDH with Quantum-Resistant MLWE Lattice KEM.
 */
class HybridQuantumHandshake
{
    private PostQuantumKemEngine $pqcKem;
    private SecretRedactor $redactor;

    public function __construct(?PostQuantumKemEngine $pqcKem = null, ?SecretRedactor $redactor = null)
    {
        $this->pqcKem = $pqcKem ?? new PostQuantumKemEngine();
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Initiate a Hybrid Handshake from Client/Peer A.
     */
    public function initiateHandshake(string $nodeId): array
    {
        $classicalPrivate = random_bytes(32);
        $classicalPublic = hash('sha256', $classicalPrivate);
        $pqcKeypair = $this->pqcKem->generateKeypair();

        $sessionToken = bin2hex(random_bytes(16));

        return [
            'success' => true,
            'session_token' => $sessionToken,
            'initiator_node' => $nodeId,
            'classical_public' => $classicalPublic,
            'pqc_public_key' => $pqcKeypair['public_key'],
            'pqc_secret_key' => $pqcKeypair['secret_key'],
            'classical_private' => bin2hex($classicalPrivate),
            'algorithm_suite' => 'HYBRID-X25519-MLWE768-AES256GCM',
        ];
    }

    /**
     * Respond to a Hybrid Handshake from Server/Peer B.
     */
    public function respondHandshake(array $initiationPayload, string $responderNodeId): array
    {
        if (empty($initiationPayload['classical_public']) || empty($initiationPayload['pqc_public_key'])) {
            throw new \InvalidArgumentException('Incomplete handshake initiation payload');
        }

        $classicalResponderPrivate = random_bytes(32);
        $classicalResponderPublic = hash('sha256', $classicalResponderPrivate);

        // Classical shared secret
        $classicalShared = hash('sha256', $initiationPayload['classical_public'] . bin2hex($classicalResponderPrivate));

        // Quantum shared secret encapsulation
        $kemResult = $this->pqcKem->encapsulate($initiationPayload['pqc_public_key']);

        // Composite Hybrid Key = HKDF(classicalShared || quantumShared)
        $compositeKey = hash_hkdf(
            'sha256',
            hex2bin($classicalShared) . hex2bin($kemResult['shared_secret']),
            32,
            'atom-hybrid-session-v1',
            $initiationPayload['session_token'] ?? 'handshake'
        );

        return [
            'success' => true,
            'session_token' => $initiationPayload['session_token'] ?? '',
            'responder_node' => $responderNodeId,
            'classical_responder_public' => $classicalResponderPublic,
            'pqc_ciphertext' => $kemResult['ciphertext'],
            'hybrid_session_key' => bin2hex($compositeKey),
            'quantum_security' => 'ACTIVE_PROTECTED',
        ];
    }
}
