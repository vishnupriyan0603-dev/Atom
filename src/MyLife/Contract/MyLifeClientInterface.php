<?php

declare(strict_types=1);

namespace Atom\MyLife\Contract;

/**
 * Interface contract for ATOM's communication client to the Samsung Galaxy M31 phone server.
 */
interface MyLifeClientInterface
{
    /**
     * Checks if the phone server is reachable on the local network.
     */
    public function ping(): MyLifeResponse;

    /**
     * Performs mutual HMAC pairing handshake using the shared secret.
     */
    public function pair(string $pairingSecret, string $clientName = 'ATOM-WINDOWS'): MyLifeResponse;

    /**
     * Retrieves server hardware, battery, and background tracking state.
     */
    public function getDeviceStatus(): MyLifeResponse;

    /**
     * Toggles pause/resume on background sensor/GPS tracking.
     */
    public function setTrackingPaused(bool $paused): MyLifeResponse;

    /**
     * Retrieves aggregated summary across all 14 life modules.
     */
    public function getExecutiveSummary(): MyLifeResponse;

    /**
     * Retrieves data for a specific life module.
     */
    public function getModuleData(MyLifeModuleEnum $module, array $params = []): MyLifeResponse;

    /**
     * Mutates or inserts a record into a specific life module.
     */
    public function postModuleAction(MyLifeModuleEnum $module, string $action, array $payload): MyLifeResponse;

    /**
     * Requests an encrypted `.mylife.enc` backup stream from the phone.
     */
    public function downloadEncryptedBackup(string $passphrase, string $destinationPath): MyLifeResponse;
}
