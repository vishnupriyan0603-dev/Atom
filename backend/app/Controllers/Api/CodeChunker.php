<?php

namespace App\Controllers\Api;

use Atom\Brain\SemanticCodeChunkerEngine;

/**
 * CodeChunker API Controller — Phase 76
 */
class CodeChunker extends BaseApiController
{
    private static ?SemanticCodeChunkerEngine $engine = null;

    private function getEngine(): SemanticCodeChunkerEngine
    {
        if (self::$engine === null) {
            self::$engine = new SemanticCodeChunkerEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/brain/chunker/split
     */
    public function split()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? "<?php\nclass UserEngine {\n  public function login() {\n    return true;\n  }\n}";
        $lang = $json['language'] ?? 'php';

        $engine = $this->getEngine();
        $res = $engine->splitCodeIntoChunks($code, $lang);

        return $this->respondSuccess($res, 'Code split into semantic AST chunks');
    }

    /**
     * POST /api/brain/chunker/call-tree
     */
    public function callTree()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '$this->validateUser(); SecretRedactor::redact($input);';

        $engine = $this->getEngine();
        $res = $engine->extractCallTree($code);

        return $this->respondSuccess($res, 'Call-tree relationships extracted');
    }
}
