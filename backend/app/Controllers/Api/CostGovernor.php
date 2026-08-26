<?php

namespace App\Controllers\Api;

use Atom\Ai\ApiCostGovernorEngine;

/**
 * CostGovernor API Controller — Phase 89
 */
class CostGovernor extends BaseApiController
{
    private static ?ApiCostGovernorEngine $engine = null;

    private function getEngine(): ApiCostGovernorEngine
    {
        if (self::$engine === null) {
            self::$engine = new ApiCostGovernorEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/ai/governor/budgets
     */
    public function budgets()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess([
            'budgets' => $engine->getAllBudgets(),
            'pricing' => $engine->getPricingMatrix(),
        ], 'Tenant LLM cost budgets retrieved');
    }

    /**
     * POST /api/ai/governor/track
     */
    public function track()
    {
        $json = $this->request->getJSON(true) ?? [];
        $tenant = $json['tenant_id'] ?? 'tenant_acme_corp';
        $model = $json['model'] ?? 'gpt-4o';
        $promptTokens = (int) ($json['prompt_tokens'] ?? 1500);
        $completionTokens = (int) ($json['completion_tokens'] ?? 400);

        $engine = $this->getEngine();
        $res = $engine->trackSpend($tenant, $model, $promptTokens, $completionTokens);

        return $this->respondSuccess($res, 'LLM spend tracked');
    }

    /**
     * POST /api/ai/governor/set-budget
     */
    public function setBudget()
    {
        $json = $this->request->getJSON(true) ?? [];
        $tenant = $json['tenant_id'] ?? 'tenant_acme_corp';
        $limit = (float) ($json['monthly_limit_usd'] ?? 150.0);

        $engine = $this->getEngine();
        $ok = $engine->setBudget($tenant, $limit);

        return $this->respondSuccess(['updated' => $ok, 'tenant_id' => $tenant, 'limit_usd' => $limit], 'Budget updated');
    }
}
