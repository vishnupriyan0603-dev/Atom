<?php

namespace App\Controllers\Api;

use Atom\Refactoring\AstCodeModernizerEngine;
use Atom\Refactoring\OwaspAutoPatcherEngine;

/**
 * CodeModernizer API Controller — Phase 47
 */
class CodeModernizer extends BaseApiController
{
    /**
     * POST /api/modernizer/upgrade
     */
    public function upgrade()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';
        $options = $json['options'] ?? [];

        if (empty(trim($code))) {
            return $this->respondError('Source code is required for modernization', 400);
        }

        $engine = new AstCodeModernizerEngine();
        $result = $engine->modernize($code, $options);

        if (!$result['success']) {
            return $this->respondError($result['error'] ?? 'Modernization failed', 400);
        }

        return $this->respondSuccess($result, 'Code successfully modernized to PHP 8.3');
    }

    /**
     * POST /api/modernizer/scan-security
     */
    public function scanSecurity()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';

        if (empty(trim($code))) {
            return $this->respondError('Source code is required for security scanning', 400);
        }

        $patcher = new OwaspAutoPatcherEngine();
        $result = $patcher->scan($code);

        return $this->respondSuccess($result, 'Security vulnerability scan completed');
    }

    /**
     * POST /api/modernizer/auto-patch
     */
    public function autoPatch()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';

        if (empty(trim($code))) {
            return $this->respondError('Source code is required for auto-patching', 400);
        }

        $patcher = new OwaspAutoPatcherEngine();
        $result = $patcher->autoPatch($code);

        return $this->respondSuccess($result, 'Security patches synthesized and applied');
    }

    /**
     * GET /api/modernizer/rules
     */
    public function rules()
    {
        return $this->respondSuccess([
            'modernization_rules' => [
                ['id' => 'switch_to_match', 'name' => 'Switch to Typed Match Expression', 'version' => 'PHP 8.0+'],
                ['id' => 'string_contains', 'name' => 'Legacy strpos to str_contains / starts_with', 'version' => 'PHP 8.0+'],
                ['id' => 'nullsafe_operator', 'name' => 'Ternary null checks to Nullsafe Operator (?->)', 'version' => 'PHP 8.0+'],
                ['id' => 'constructor_promotion', 'name' => 'Constructor Property Promotion', 'version' => 'PHP 8.1+'],
                ['id' => 'readonly_classes', 'name' => 'Immutable Classes to Readonly', 'version' => 'PHP 8.2+'],
            ],
            'owasp_detectors' => [
                ['cwe' => 'CWE-89', 'name' => 'SQL Injection (Prepared Statement Bindings)', 'severity' => 'CRITICAL'],
                ['cwe' => 'CWE-79', 'name' => 'Cross-Site Scripting (HTML Entity Encoding)', 'severity' => 'HIGH'],
                ['cwe' => 'CWE-22', 'name' => 'Path Traversal (Basename Directory Isolation)', 'severity' => 'HIGH'],
                ['cwe' => 'CWE-78', 'name' => 'OS Command Injection (Shell Escaping)', 'severity' => 'CRITICAL'],
            ],
        ], 'Modernization and security rule catalogue');
    }
}
