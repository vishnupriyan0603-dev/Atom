<?php

namespace App\Controllers\Api;

use Atom\Infrastructure\WasmSandboxRuntimeEngine;

/**
 * WasmSandbox API Controller — Phase 101
 */
class WasmSandbox extends BaseApiController
{
    private static ?WasmSandboxRuntimeEngine $engine = null;

    private function getEngine(): WasmSandboxRuntimeEngine
    {
        if (self::$engine === null) {
            self::$engine = new WasmSandboxRuntimeEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/infrastructure/wasm/execute
     */
    public function execute()
    {
        $json = $this->request->getJSON(true) ?? [];
        $func = $json['function'] ?? 'vector_dot_product';
        $args = $json['args'] ?? [[1.5, 2.5, 3.0], [2.0, 4.0, 1.0]];
        $gas = (int)($json['gas_limit'] ?? 10000);

        $engine = $this->getEngine();
        $res = $engine->execute($func, $args, $gas);

        return $this->respondSuccess($res, 'Wasm sandboxed routine executed');
    }

    /**
     * GET /api/infrastructure/wasm/runtimes
     */
    public function runtimes()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getRuntimeProfile(), 'Wasm sandbox runtime profile');
    }
}
