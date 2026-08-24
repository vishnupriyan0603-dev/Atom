<?php

namespace App\Controllers\Api;

use Atom\Refactoring\CodeSmellDetector;
use Atom\Refactoring\ASTTransformationEngine;
use Atom\Refactoring\DependencyGraphAnalyzer;
use Atom\Refactoring\RefactorSafetyVerifier;

/**
 * Autonomous Code Refactoring & Micro-Architecture API Controller — Phase 35
 *
 * Endpoints:
 * - POST /api/v1/refactor/smells       — Scan source code and detect code smells
 * - POST /api/v1/refactor/transform    — Apply automated AST transformation
 * - POST /api/v1/refactor/dependencies — Compute dependency graph and circular cycles
 * - POST /api/v1/refactor/verify       — Verify syntactic & semantic invariant safety
 */
class Refactoring extends BaseApiController
{
    private static ?CodeSmellDetector $smellInstance = null;
    private static ?ASTTransformationEngine $astInstance = null;
    private static ?DependencyGraphAnalyzer $depInstance = null;
    private static ?RefactorSafetyVerifier $verifierInstance = null;

    private function getSmellDetector(): CodeSmellDetector
    {
        if (self::$smellInstance === null) {
            self::$smellInstance = new CodeSmellDetector();
        }
        return self::$smellInstance;
    }

    private function getASTEngine(): ASTTransformationEngine
    {
        if (self::$astInstance === null) {
            self::$astInstance = new ASTTransformationEngine();
        }
        return self::$astInstance;
    }

    private function getDependencyAnalyzer(): DependencyGraphAnalyzer
    {
        if (self::$depInstance === null) {
            self::$depInstance = new DependencyGraphAnalyzer();
        }
        return self::$depInstance;
    }

    private function getVerifier(): RefactorSafetyVerifier
    {
        if (self::$verifierInstance === null) {
            self::$verifierInstance = new RefactorSafetyVerifier();
        }
        return self::$verifierInstance;
    }

    /**
     * POST /api/v1/refactor/smells
     */
    public function smells()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';

        $detector = $this->getSmellDetector();
        $result = $detector->scan($code);

        return $this->respondSuccess($result, 'Code smells scanned');
    }

    /**
     * POST /api/v1/refactor/transform
     */
    public function transform()
    {
        $json = $this->request->getJSON(true) ?? [];
        $type = $json['type'] ?? 'extract_method';
        $code = $json['code'] ?? '';
        $options = $json['options'] ?? [];

        $ast = $this->getASTEngine();
        try {
            $result = $ast->transform($type, $code, $options);
            return $this->respondSuccess($result, "Transformation '{$type}' applied successfully");
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/refactor/dependencies
     */
    public function dependencies()
    {
        $json = $this->request->getJSON(true) ?? [];
        $graph = $json['graph'] ?? [];

        $analyzer = $this->getDependencyAnalyzer();
        $result = $analyzer->analyze($graph);

        return $this->respondSuccess($result, 'Dependency graph analyzed');
    }

    /**
     * POST /api/v1/refactor/verify
     */
    public function verify()
    {
        $json = $this->request->getJSON(true) ?? [];
        $original = $json['original'] ?? '';
        $refactored = $json['refactored'] ?? '';

        $verifier = $this->getVerifier();
        $result = $verifier->verify($original, $refactored);

        return $this->respondSuccess($result, 'Refactor safety verified');
    }
}
