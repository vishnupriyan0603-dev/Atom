<?php

namespace App\Controllers\Api;

use Atom\Refactoring\OpenApiSdkGeneratorEngine;

/**
 * OpenApiSdk API Controller — Phase 57
 */
class OpenApiSdk extends BaseApiController
{
    private static ?OpenApiSdkGeneratorEngine $engine = null;

    private function getEngine(): OpenApiSdkGeneratorEngine
    {
        if (self::$engine === null) {
            self::$engine = new OpenApiSdkGeneratorEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/docs/openapi.json
     */
    public function openApiJson()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->generateOpenApiSpec(), 'OpenAPI 3.1.0 schema generated');
    }

    /**
     * POST /api/docs/generate-sdk
     */
    public function generateSdk()
    {
        $json = $this->request->getJSON(true) ?? [];
        $lang = $json['language'] ?? 'typescript';

        $engine = $this->getEngine();
        $sdk = $engine->generateSdk($lang);

        return $this->respondSuccess($sdk, 'Client SDK generated');
    }
}
