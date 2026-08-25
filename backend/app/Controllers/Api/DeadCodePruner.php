<?php

namespace App\Controllers\Api;

use Atom\Refactoring\AstDeadCodeEliminatorEngine;

/**
 * DeadCodePruner API Controller — Phase 54
 */
class DeadCodePruner extends BaseApiController
{
    /**
     * POST /api/refactoring/dead-code/scan
     */
    public function scan()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';

        if (empty(trim($code))) {
            return $this->respondError('Source code is required', 400);
        }

        $engine = new AstDeadCodeEliminatorEngine();
        $result = $engine->scan($code);

        return $this->respondSuccess($result, 'Dead code scan completed');
    }

    /**
     * POST /api/refactoring/dead-code/prune
     */
    public function prune()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';

        if (empty(trim($code))) {
            return $this->respondError('Source code is required', 400);
        }

        $engine = new AstDeadCodeEliminatorEngine();
        $result = $engine->prune($code);

        return $this->respondSuccess($result, 'Dead code pruned successfully');
    }
}
