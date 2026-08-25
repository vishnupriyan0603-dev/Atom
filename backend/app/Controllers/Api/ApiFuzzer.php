<?php

namespace App\Controllers\Api;

use Atom\Testing\ApiSchemaFuzzerEngine;

/**
 * ApiFuzzer API Controller — Phase 74
 */
class ApiFuzzer extends BaseApiController
{
    private static ?ApiSchemaFuzzerEngine $engine = null;

    private function getEngine(): ApiSchemaFuzzerEngine
    {
        if (self::$engine === null) {
            self::$engine = new ApiSchemaFuzzerEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/testing/fuzzer/scan
     */
    public function scan()
    {
        $json = $this->request->getJSON(true) ?? [];
        $endpoint = $json['endpoint'] ?? '/api/users/profile';
        $params = $json['params'] ?? ['user_id' => 'int', 'filter' => 'string'];

        $engine = $this->getEngine();
        $res = $engine->fuzzEndpoint($endpoint, $params);

        return $this->respondSuccess($res, 'API endpoint fuzzed');
    }

    /**
     * GET /api/testing/fuzzer/payloads
     */
    public function payloads()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getFuzzPayloads(), 'Active fuzzing payloads');
    }
}
