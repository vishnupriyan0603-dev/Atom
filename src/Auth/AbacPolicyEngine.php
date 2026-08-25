<?php

namespace Atom\Auth;

use Atom\Security\SecretRedactor;

/**
 * AbacPolicyEngine — Phase 48
 * Dynamic Attribute-Based Access Control (ABAC) & Zero-Trust Contextual Policy Engine.
 * Evaluates Subject, Resource, Action, and Environment attributes with fine-grained rule matching.
 */
class AbacPolicyEngine
{
    private SecretRedactor $redactor;
    private string $combiningAlgorithm; // 'DenyOverrides' | 'PermitOverrides' | 'FirstApplicable'

    public function __construct(string $combiningAlgorithm = 'DenyOverrides', ?SecretRedactor $redactor = null)
    {
        $this->combiningAlgorithm = $combiningAlgorithm;
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Evaluate an access decision request against a set of ABAC policies.
     *
     * @param array $request [ 'subject' => [...], 'resource' => [...], 'action' => '...', 'environment' => [...] ]
     * @param array $policies Array of policy definitions
     * @return array [ 'decision' => 'PERMIT'|'DENY', 'matched_policy' => ?string, 'evaluation_trace' => array ]
     */
    public function evaluate(array $request, array $policies): array
    {
        $subject = $request['subject'] ?? [];
        $resource = $request['resource'] ?? [];
        $action = strtolower((string)($request['action'] ?? 'read'));
        $environment = $request['environment'] ?? [];

        $evaluationTrace = [];
        $permits = [];
        $denies = [];

        foreach ($policies as $policy) {
            $policyId = $policy['id'] ?? 'unknown_policy';
            $effect = strtoupper($policy['effect'] ?? 'DENY');

            // 1. Check Target Match (Resource type & Action)
            if (!$this->matchesTarget($policy['target'] ?? [], $resource, $action)) {
                $evaluationTrace[] = [
                    'policy_id' => $policyId,
                    'status' => 'NOT_APPLICABLE',
                    'reason' => 'Target resource or action did not match policy scope',
                ];
                continue;
            }

            // 2. Evaluate Rule Conditions (Subject, Resource, Environment)
            $conditionResult = $this->evaluateConditions($policy['rules'] ?? [], $subject, $resource, $environment, $failedRule);

            if ($conditionResult) {
                $evaluationTrace[] = [
                    'policy_id' => $policyId,
                    'status' => 'MATCHED',
                    'effect' => $effect,
                    'reason' => 'All policy attribute conditions satisfied',
                ];

                if ($effect === 'PERMIT') {
                    $permits[] = $policyId;
                } else {
                    $denies[] = $policyId;
                }

                if ($this->combiningAlgorithm === 'FirstApplicable') {
                    return [
                        'decision' => $effect,
                        'matched_policy' => $policyId,
                        'combining_algorithm' => $this->combiningAlgorithm,
                        'evaluation_trace' => $evaluationTrace,
                    ];
                }
            } else {
                $evaluationTrace[] = [
                    'policy_id' => $policyId,
                    'status' => 'CONDITION_FAILED',
                    'reason' => $failedRule ?? 'Attribute rule condition not satisfied',
                ];
            }
        }

        // Apply combining algorithm
        $decision = 'DENY'; // Default Zero-Trust close
        $matchedPolicy = null;

        if ($this->combiningAlgorithm === 'PermitOverrides') {
            if (!empty($permits)) {
                $decision = 'PERMIT';
                $matchedPolicy = $permits[0];
            } elseif (!empty($denies)) {
                $decision = 'DENY';
                $matchedPolicy = $denies[0];
            }
        } else {
            // DenyOverrides (Default)
            if (!empty($denies)) {
                $decision = 'DENY';
                $matchedPolicy = $denies[0];
            } elseif (!empty($permits)) {
                $decision = 'PERMIT';
                $matchedPolicy = $permits[0];
            }
        }

        return [
            'decision' => $decision,
            'matched_policy' => $matchedPolicy,
            'combining_algorithm' => $this->combiningAlgorithm,
            'permits_count' => count($permits),
            'denies_count' => count($denies),
            'evaluation_trace' => $evaluationTrace,
        ];
    }

    private function matchesTarget(array $target, array $resource, string $action): array|bool
    {
        if (!empty($target['resource_type'])) {
            $targetType = is_array($target['resource_type']) ? $target['resource_type'] : [$target['resource_type']];
            if (!in_array('*', $targetType) && !in_array($resource['type'] ?? '', $targetType)) {
                return false;
            }
        }

        if (!empty($target['actions'])) {
            $targetActions = array_map('strtolower', is_array($target['actions']) ? $target['actions'] : [$target['actions']]);
            if (!in_array('*', $targetActions) && !in_array($action, $targetActions)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateConditions(array $rules, array $subject, array $resource, array $environment, ?string &$failedRule = null): bool
    {
        foreach ($rules as $rule) {
            $attrCategory = $rule['category'] ?? 'subject';
            $attrName = $rule['attribute'] ?? '';
            $operator = strtolower($rule['operator'] ?? 'equals');
            $expectedValue = $rule['value'] ?? null;

            $actualValue = match ($attrCategory) {
                'resource' => $resource[$attrName] ?? null,
                'environment' => $environment[$attrName] ?? null,
                default => $subject[$attrName] ?? null,
            };

            if (!$this->checkOperator($operator, $actualValue, $expectedValue)) {
                $failedRule = "Rule failed: {$attrCategory}.{$attrName} ({$actualValue}) {$operator} " . json_encode($expectedValue);
                return false;
            }
        }

        return true;
    }

    private function checkOperator(string $op, mixed $actual, mixed $expected): bool
    {
        if ($actual === null && !in_array($op, ['is_null', 'is_empty'])) {
            return false;
        }

        return match ($op) {
            'equals', '==' => (string)$actual === (string)$expected,
            'not_equals', '!=' => (string)$actual !== (string)$expected,
            'greater_than', '>' => (float)$actual > (float)$expected,
            'greater_equals', '>=' => (float)$actual >= (float)$expected,
            'less_than', '<' => (float)$actual < (float)$expected,
            'less_equals', '<=' => (float)$actual <= (float)$expected,
            'in' => is_array($expected) && in_array($actual, $expected),
            'not_in' => is_array($expected) && !in_array($actual, $expected),
            'contains' => is_string($actual) && str_contains($actual, (string)$expected),
            'is_true' => (bool)$actual === true,
            'is_false' => (bool)$actual === false,
            'cidr_match' => $this->ipInCidr((string)$actual, (string)$expected),
            default => (string)$actual === (string)$expected,
        };
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        if ($cidr === '*' || $cidr === '0.0.0.0/0') return true;
        if (!str_contains($cidr, '/')) return $ip === $cidr;

        [$subnet, $mask] = explode('/', $cidr);
        if ((int)$mask <= 0 || (int)$mask > 32) return false;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) return false;

        $maskLong = -1 << (32 - (int)$mask);
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
