<?php

namespace App\Controllers\Api;

use Atom\Refactoring\AstCodeLinterEngine;

/**
 * CodeLinter API Controller — Phase 63
 */
class CodeLinter extends BaseApiController
{
    private static ?AstCodeLinterEngine $engine = null;

    private function getEngine(): AstCodeLinterEngine
    {
        if (self::$engine === null) {
            self::$engine = new AstCodeLinterEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/refactoring/linter/scan
     */
    public function scan()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? "<?php\n\nclass SampleController {\n    public function index() {\n        return 'hello';\n    }\n}\n?>";

        $engine = $this->getEngine();
        $res = $engine->scanCode($code);

        return $this->respondSuccess($res, 'Code scanned for PSR-12 violations');
    }

    /**
     * POST /api/refactoring/linter/fix
     */
    public function fix()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? "<?php\n\nclass SampleController {\n    public function index() {\n        return 'hello';\n    }\n}\n?>";

        $engine = $this->getEngine();
        $res = $engine->fixCode($code);

        return $this->respondSuccess($res, 'Code auto-fixed to PSR-12 standards');
    }
}
