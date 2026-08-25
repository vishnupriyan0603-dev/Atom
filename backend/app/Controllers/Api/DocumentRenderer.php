<?php

namespace App\Controllers\Api;

use Atom\Document\MarkdownPdfRendererEngine;

/**
 * DocumentRenderer API Controller — Phase 84
 */
class DocumentRenderer extends BaseApiController
{
    private static ?MarkdownPdfRendererEngine $engine = null;

    private function getEngine(): MarkdownPdfRendererEngine
    {
        if (self::$engine === null) {
            self::$engine = new MarkdownPdfRendererEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/documents/render/pdf
     */
    public function renderPdf()
    {
        $json = $this->request->getJSON(true) ?? [];
        $markdown = $json['markdown'] ?? "# Sample Title\n\nThis is a sample markdown document.";
        $title = $json['title'] ?? 'Exported PDF Document';
        $theme = $json['theme'] ?? 'corporate';

        $engine = $this->getEngine();
        $res = $engine->renderDocument($markdown, $title, $theme);

        return $this->respondSuccess($res, 'Document rendered to print-ready format');
    }

    /**
     * GET /api/documents/templates
     */
    public function templates()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getDocumentTemplates(), 'Document templates retrieved');
    }
}
