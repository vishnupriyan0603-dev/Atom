<?php

namespace App\Controllers\Api;

use Atom\Brain\NaturalDialogueOrchestratorEngine;

/**
 * NaturalDialogue API Controller — Phase 69
 */
class NaturalDialogue extends BaseApiController
{
    private static ?NaturalDialogueOrchestratorEngine $engine = null;

    private function getEngine(): NaturalDialogueOrchestratorEngine
    {
        if (self::$engine === null) {
            self::$engine = new NaturalDialogueOrchestratorEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/brain/dialogue/respond
     */
    public function respond()
    {
        $json = $this->request->getJSON(true) ?? [];
        $text = $json['message'] ?? 'hello';
        $context = $json['context'] ?? [];

        $engine = $this->getEngine();
        $result = $engine->processTurn($text, $context);

        return $this->respondSuccess($result, 'Dialogue turn processed');
    }

    /**
     * POST /api/brain/dialogue/teach
     */
    public function teach()
    {
        $json = $this->request->getJSON(true) ?? [];
        $concept = $json['concept'] ?? 'Database Indexing';
        $explanation = $json['explanation'] ?? 'Indexes act like a book index to speed up searches.';
        $example = $json['example'] ?? 'Finding a name in a phonebook sorted alphabetically.';
        $advice = $json['advice'] ?? 'Index frequently queried foreign keys and filter columns.';

        $engine = $this->getEngine();
        $res = $engine->structureTeachingExplanation($concept, $explanation, $example, $advice);

        return $this->respondSuccess($res, 'Pedagogical explanation structured');
    }

    /**
     * POST /api/brain/dialogue/english-hint
     */
    public function englishHint()
    {
        $json = $this->request->getJSON(true) ?? [];
        $text = $json['text'] ?? '';

        $engine = $this->getEngine();
        $hint = $engine->analyzeEnglishGuidance($text);

        return $this->respondSuccess([
            'has_hint' => $hint !== null,
            'hint' => $hint,
        ], 'English learning analysis');
    }
}
