<?php

namespace Atom\Security;

/**
 * GeoFencingFirewallEngine — Phase 64
 * Zero-trust IP geolocation resolver and country-level geo-fencing access firewall.
 */
class GeoFencingFirewallEngine
{
    private SecretRedactor $redactor;
    private array $allowedCountries = ['IN', 'US', 'GB', 'DE', 'SG', 'CA', 'AU'];
    private array $blockedCountries = ['KP', 'IR', 'SY', 'CU'];
    private array $blockedCidrs = ['198.51.100.0/24', '203.0.113.0/24'];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Resolve IP address to simulated geographic metadata.
     */
    public function resolveIp(string $ip): array
    {
        $cleanIp = trim($ip);

        // Private / Loopback IPs
        if ($cleanIp === '127.0.0.1' || $cleanIp === '::1' || str_starts_with($cleanIp, '192.168.') || str_starts_with($cleanIp, '10.')) {
            return [
                'ip' => $cleanIp,
                'country_code' => 'LOCAL',
                'country_name' => 'Localhost / Private Network',
                'city' => 'Internal LAN',
                'lat' => 13.0827,
                'lon' => 80.2707,
                'is_private' => true,
            ];
        }

        // Deterministic mock lookup based on IP hash
        $hash = crc32($cleanIp);
        $countries = [
            0 => ['code' => 'IN', 'name' => 'India', 'city' => 'Chennai', 'lat' => 13.0827, 'lon' => 80.2707],
            1 => ['code' => 'US', 'name' => 'United States', 'city' => 'San Jose', 'lat' => 37.3382, 'lon' => -121.8863],
            2 => ['code' => 'GB', 'name' => 'United Kingdom', 'city' => 'London', 'lat' => 51.5074, 'lon' => -0.1278],
            3 => ['code' => 'KP', 'name' => 'North Korea', 'city' => 'Pyongyang', 'lat' => 39.0392, 'lon' => 125.7625],
            4 => ['code' => 'DE', 'name' => 'Germany', 'city' => 'Frankfurt', 'lat' => 50.1109, 'lon' => 8.6821],
        ];

        $matched = $countries[abs($hash) % count($countries)];

        return [
            'ip' => $cleanIp,
            'country_code' => $matched['code'],
            'country_name' => $matched['name'],
            'city' => $matched['city'],
            'lat' => $matched['lat'],
            'lon' => $matched['lon'],
            'is_private' => false,
        ];
    }

    /**
     * Evaluate incoming client IP against geo-fencing zero-trust policies.
     */
    public function evaluateAccess(string $ip, string $mode = 'allowlist'): array
    {
        $geo = $this->resolveIp($ip);

        // Always allow local private network
        if ($geo['is_private']) {
            return [
                'allowed' => true,
                'reason' => 'ACCESS_GRANTED_LOCAL_NETWORK',
                'ip' => $geo['ip'],
                'geo' => $geo,
            ];
        }

        // Check CIDR blocklist
        foreach ($this->blockedCidrs as $cidr) {
            if ($this->ipInCidr($geo['ip'], $cidr)) {
                return [
                    'allowed' => false,
                    'reason' => 'BLOCKED_BY_CIDR_RULE',
                    'matched_cidr' => $cidr,
                    'ip' => $geo['ip'],
                    'geo' => $geo,
                ];
            }
        }

        $country = strtoupper($geo['country_code']);

        // Blocklist check
        if (in_array($country, $this->blockedCountries, true)) {
            return [
                'allowed' => false,
                'reason' => 'BLOCKED_BY_COUNTRY_BLACKLIST',
                'country' => $country,
                'ip' => $geo['ip'],
                'geo' => $geo,
            ];
        }

        // Allowlist check
        if (strtolower($mode) === 'allowlist' && !in_array($country, $this->allowedCountries, true)) {
            return [
                'allowed' => false,
                'reason' => 'BLOCKED_NOT_IN_COUNTRY_ALLOWLIST',
                'country' => $country,
                'ip' => $geo['ip'],
                'geo' => $geo,
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'ACCESS_GRANTED_POLICY_PASSED',
            'country' => $country,
            'ip' => $geo['ip'],
            'geo' => $geo,
        ];
    }

    /**
     * Helper to verify if an IPv4 address falls within a CIDR range.
     */
    public function ipInCidr(string $ip, string $cidr): bool
    {
        $parts = explode('/', $cidr);
        if (count($parts) !== 2) return false;

        $subnet = $parts[0];
        $mask = (int) $parts[1];

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) return false;

        $maskBits = ~((1 << (32 - $mask)) - 1);
        return ($ipLong & $maskBits) === ($subnetLong & $maskBits);
    }

    public function getPolicy(): array
    {
        return [
            'allowed_countries' => $this->allowedCountries,
            'blocked_countries' => $this->blockedCountries,
            'blocked_cidrs' => $this->blockedCidrs,
        ];
    }
}
