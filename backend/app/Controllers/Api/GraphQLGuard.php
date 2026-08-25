<?php

namespace App\Controllers\Api;

use Atom\Api\GraphQLComplexityAnalyzerEngine;

/**
 * GraphQLGuard API Controller — Phase 83
 */
class GraphQLGuard extends BaseApiController
{
    private static ?GraphQLComplexityAnalyzerEngine $engine = null;

    private function getEngine(): GraphQLComplexityAnalyzerEngine
    {
        if (self::$engine === null) {
            self::$engine = new GraphQLComplexityAnalyzerEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/api/graphql/analyze
     */
    public function analyze()
    {
        $json = $this->request->getJSON(true) ?? [];
        $query = $json['query'] ?? '{ user(id: "123") { id name email orders { id total } } }';

        $engine = $this->getEngine();
        $res = $engine->analyzeQuery($query);

        return $this->respondSuccess($res, 'GraphQL query analyzed');
    }

    /**
     * GET /api/api/graphql/budgets
     */
    public function budgets()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getBudgetLimits(), 'GraphQL budget limits');
    }
}
