<?php

namespace App\Controllers\Api;

use Atom\Security\PostQuantumKemEngine;
use Atom\Security\PostQuantumSignatureEngine;
use Atom\Security\HybridQuantumHandshake;

/**
 * PostQuantum API Controller — Phase 45
 */
class PostQuantum extends BaseApiController
{
    /**
     * POST /api/pqc/kem/keypair
     */
    public function keypair()
    {
        $kem = new PostQuantumKemEngine();
        $keypair = $kem->generateKeypair();

        return $this->respondSuccess($keypair, 'Post-Quantum KEM keypair generated');
    }

    /**
     * POST /api/pqc/kem/encapsulate
     */
    public function encapsulate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $publicKey = $json['public_key'] ?? '';

        if (empty($publicKey)) {
            return $this->respondError('Public key is required for encapsulation', 400);
        }

        try {
            $kem = new PostQuantumKemEngine();
            $result = $kem->encapsulate($publicKey);

            return $this->respondSuccess($result, 'Quantum shared secret encapsulated');
        } catch (\Throwable $e) {
            return $this->respondError('Encapsulation error: ' . $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/pqc/kem/decapsulate
     */
    public function decapsulate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $ciphertext = $json['ciphertext'] ?? '';
        $secretKey = $json['secret_key'] ?? '';

        if (empty($ciphertext) || empty($secretKey)) {
            return $this->respondError('Ciphertext and Secret Key are required for decapsulation', 400);
        }

        try {
            $kem = new PostQuantumKemEngine();
            $result = $kem->decapsulate($ciphertext, $secretKey);

            return $this->respondSuccess($result, 'Quantum shared secret decapsulated');
        } catch (\Throwable $e) {
            return $this->respondError('Decapsulation error: ' . $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/pqc/sign
     */
    public function sign()
    {
        $json = $this->request->getJSON(true) ?? [];
        $message = $json['message'] ?? '';
        $signingKey = $json['signing_key'] ?? '';

        $sigEngine = new PostQuantumSignatureEngine();

        if (empty($signingKey)) {
            // Generate ephemeral keypair if not provided
            $kp = $sigEngine->generateKeypair();
            $signingKey = $kp['signing_key'];
            $verificationKey = $kp['verification_key'];
        }

        if (empty($message)) {
            return $this->respondError('Message is required for signing', 400);
        }

        try {
            $result = $sigEngine->sign($message, $signingKey);
            if (isset($verificationKey)) {
                $result['verification_key'] = $verificationKey;
            }

            return $this->respondSuccess($result, 'Message signed with post-quantum digital signature');
        } catch (\Throwable $e) {
            return $this->respondError('Signing error: ' . $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/pqc/verify
     */
    public function verify()
    {
        $json = $this->request->getJSON(true) ?? [];
        $message = $json['message'] ?? '';
        $signature = $json['signature'] ?? '';
        $verificationKey = $json['verification_key'] ?? '';

        if (empty($message) || empty($signature) || empty($verificationKey)) {
            return $this->respondError('Message, signature, and verification_key are required', 400);
        }

        $sigEngine = new PostQuantumSignatureEngine();
        $valid = $sigEngine->verify($message, $signature, $verificationKey);

        return $this->respondSuccess([
            'valid' => $valid,
            'status' => $valid ? 'SIGNATURE_AUTHENTICATED' : 'SIGNATURE_INVALID',
            'algorithm' => 'ATOM-MLWE-SIG-5',
        ], 'Quantum signature verification completed');
    }

    /**
     * POST /api/pqc/handshake
     */
    public function handshake()
    {
        $json = $this->request->getJSON(true) ?? [];
        $nodeId = $json['node_id'] ?? ('node_' . bin2hex(random_bytes(4)));

        $handshake = new HybridQuantumHandshake();
        $initiation = $handshake->initiateHandshake($nodeId);
        $response = $handshake->respondHandshake($initiation, 'atom_core_gateway');

        return $this->respondSuccess([
            'initiation' => $initiation,
            'response' => $response,
            'handshake_complete' => true,
            'cipher_suite' => 'HYBRID-X25519-MLWE768-AES256GCM',
        ], 'Hybrid quantum zero-trust handshake established');
    }
}
