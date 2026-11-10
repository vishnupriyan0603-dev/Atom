<?php

declare(strict_types=1);

namespace Atom\MyLife\Client;

final class MyLifeSigner
{
    public static function pairingSignature(
        string $secret,
        string $clientId,
        string $deviceName,
        string $fingerprint,
        string $timestamp
    ): string {
        return hash_hmac('sha256', implode("\n", [$clientId, $deviceName, $fingerprint, $timestamp]), $secret);
    }

    public static function requestSignature(
        string $secret,
        string $method,
        string $requestUri,
        string $timestamp,
        string $body
    ): string {
        $bodyHash = hash('sha256', $body);
        $message = strtoupper($method) . "\n" . $requestUri . "\n" . $timestamp . "\n" . $bodyHash;

        return hash_hmac('sha256', $message, $secret);
    }
}
