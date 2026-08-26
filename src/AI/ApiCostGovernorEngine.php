<?php

namespace Atom\Ai;

use Atom\Security\SecretRedactor;

/**
 * ApiCostGovernorEngine — Phase 89
 * Multi-model LLM token cost accountant, tenant budget enforcer, and cost optimization advisor.
 */
class ApiCostGovernorEngine
{
    private SecretRedactor $redactor;
    private array $tenantBudgets = [];
    private array $pricing = [
        'gpt-4o' => ['input_per_million' => 2.50, 'output_per_million' => 10.00],
        'claude-3-5-sonnet' => ['input_per_million' => 3.00, 'output_per_million' => 15.00],
        'gemini-1-5-pro' => ['input_per_million' => 1.25, 'output_per_million' => 5.00],
        'atom-neural-edge' => ['input_per_million' => 0.00, 'output_per_million' => 0.00],
    ];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleBudgets();
    }

    /**
     * Set or configure a tenant's LLM budget limit.
     */
    public function setBudget(string $tenantId, float $monthlyLimitUsd = 100.0): bool
    {
        $cleanId = trim(strtolower($this->redactor->redact($tenantId)));

        if (!isset($this->tenantBudgets[$cleanId])) {
            $this->tenantBudgets[$cleanId] = [
                'tenant_id' => $cleanId,
                'monthly_limit_usd' => max(5.0, $monthlyLimitUsd),
                'current_spend_usd' => 0.0,
                'total_prompt_tokens' => 0,
                'total_completion_tokens' => 0,
                'model_spend_breakdown' => [],
                'status' => 'HEALTHY', // HEALTHY, WARNING_80_PCT, THROTTLED_BUDGET_EXCEEDED
            ];
        } else {
            $this->tenantBudgets[$cleanId]['monthly_limit_usd'] = max(5.0, $monthlyLimitUsd);
            $this->updateTenantStatus($cleanId);
        }

        return true;
    }

    /**
     * Track an LLM API call, calculate exact cost, and check budget compliance.
     */
    public function trackSpend(string $tenantId, string $model, int $promptTokens, int $completionTokens): array
    {
        $cleanId = trim(strtolower($this->redactor->redact($tenantId)));
        $cleanModel = strtolower(trim($model));

        if (!isset($this->tenantBudgets[$cleanId])) {
            $this->setBudget($cleanId, 50.0);
        }

        $rates = $this->pricing[$cleanModel] ?? $this->pricing['gpt-4o'];
        $costInput = ($promptTokens / 1_000_000.0) * $rates['input_per_million'];
        $costOutput = ($completionTokens / 1_000_000.0) * $rates['output_per_million'];
        $callCost = $costInput + $costOutput;

        $tenant = &$this->tenantBudgets[$cleanId];

        // Check if already throttled
        if ($tenant['current_spend_usd'] + $callCost > $tenant['monthly_limit_usd']) {
            $tenant['status'] = 'THROTTLED_BUDGET_EXCEEDED';
            return [
                'allowed' => false,
                'status' => 'THROTTLED_BUDGET_EXCEEDED',
                'tenant_id' => $cleanId,
                'model' => $cleanModel,
                'call_cost_usd' => round($callCost, 6),
                'current_spend_usd' => round($tenant['current_spend_usd'], 4),
                'monthly_limit_usd' => $tenant['monthly_limit_usd'],
                'recommendation' => 'Route request to local zero-cost atom-neural-edge model.',
            ];
        }

        $tenant['current_spend_usd'] += $callCost;
        $tenant['total_prompt_tokens'] += $promptTokens;
        $tenant['total_completion_tokens'] += $completionTokens;
        $tenant['model_spend_breakdown'][$cleanModel] = ($tenant['model_spend_breakdown'][$cleanModel] ?? 0.0) + $callCost;

        $this->updateTenantStatus($cleanId);

        return [
            'allowed' => true,
            'status' => $tenant['status'],
            'tenant_id' => $cleanId,
            'model' => $cleanModel,
            'call_cost_usd' => round($callCost, 6),
            'current_spend_usd' => round($tenant['current_spend_usd'], 4),
            'monthly_limit_usd' => $tenant['monthly_limit_usd'],
            'budget_remaining_usd' => round(max(0.0, $tenant['monthly_limit_usd'] - $tenant['current_spend_usd']), 4),
            'recommendation' => $tenant['status'] === 'WARNING_80_PCT' ? 'Consider enabling semantic caching to reduce cost.' : 'Spend within healthy budget.',
        ];
    }

    private function updateTenantStatus(string $tenantId): void
    {
        $tenant = &$this->tenantBudgets[$tenantId];
        $pct = ($tenant['current_spend_usd'] / max(0.01, $tenant['monthly_limit_usd'])) * 100.0;

        if ($pct >= 100.0) {
            $tenant['status'] = 'THROTTLED_BUDGET_EXCEEDED';
        } elseif ($pct >= 80.0) {
            $tenant['status'] = 'WARNING_80_PCT';
        } else {
            $tenant['status'] = 'HEALTHY';
        }
    }

    public function getAllBudgets(): array
    {
        $res = [];
        foreach ($this->tenantBudgets as $t) {
            $pct = round(($t['current_spend_usd'] / max(0.01, $t['monthly_limit_usd'])) * 100.0, 1);
            $res[] = array_merge($t, [
                'spend_pct' => $pct,
                'current_spend_usd' => round($t['current_spend_usd'], 4),
            ]);
        }
        return $res;
    }

    public function getPricingMatrix(): array
    {
        return $this->pricing;
    }

    private function seedSampleBudgets(): void
    {
        $this->setBudget('tenant_acme_corp', 250.0);
        $this->setBudget('tenant_startup_inc', 50.0);
        $this->setBudget('tenant_research_lab', 500.0);

        $this->trackSpend('tenant_acme_corp', 'gpt-4o', 120_000, 45_000);
        $this->trackSpend('tenant_startup_inc', 'claude-3-5-sonnet', 80_000, 30_000);
    }
}
