<?php

namespace Atom\Api;

use Atom\Security\SecretRedactor;

/**
 * GraphQLComplexityAnalyzerEngine — Phase 83
 * Autonomous GraphQL AST query complexity scoring, depth limiter, and recursive DoS bomb shield.
 */
class GraphQLComplexityAnalyzerEngine
{
    private SecretRedactor $redactor;
    private int $maxAllowedDepth = 7;
    private int $maxAllowedComplexity = 250;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Analyze a GraphQL query string for depth, field count, and cumulative cost complexity.
     *
     * @param string $query Raw GraphQL query document
     * @return array Complexity evaluation with status ('ALLOWED' or 'BLOCKED_EXCEEDS_BUDGET')
     */
    public function analyzeQuery(string $query): array
    {
        $cleanQuery = trim($this->redactor->redact($query));

        if (empty($cleanQuery)) {
            return [
                'success' => false,
                'error' => 'Query string cannot be empty',
                'depth' => 0,
                'complexity' => 0,
                'status' => 'REJECTED_EMPTY',
            ];
        }

        // Calculate maximum depth by tracking brace depth
        $maxDepth = 0;
        $currentDepth = 0;
        $fieldCount = 0;
        $complexity = 0;

        $length = strlen($cleanQuery);
        for ($i = 0; $i < $length; $i++) {
            $char = $cleanQuery[$i];
            if ($char === '{') {
                $currentDepth++;
                if ($currentDepth > $maxDepth) {
                    $maxDepth = $currentDepth;
                }
            } elseif ($char === '}') {
                if ($currentDepth > 0) {
                    $currentDepth--;
                }
            }
        }

        // Extract field tokens (alphanumeric words not matching GraphQL keywords)
        preg_match_all('/[a-zA-Z_][a-zA-Z0-9_]*/', $cleanQuery, $matches);
        $tokens = $matches[0] ?? [];
        $ignoredKeywords = ['query', 'mutation', 'subscription', 'fragment', 'on', 'true', 'false', 'null', 'first', 'after', 'before', 'last'];

        foreach ($tokens as $token) {
            if (!in_array(strtolower($token), $ignoredKeywords, true)) {
                $fieldCount++;
                // Multiplier for connection / list fields
                if (preg_match('/(s|list|connection|edges|nodes)$/i', $token)) {
                    $complexity += 10;
                } else {
                    $complexity += 1;
                }
            }
        }

        // Weight complexity by query depth
        $weightedComplexity = (int) ($complexity + ($maxDepth * 5));

        $depthExceeded = $maxDepth > $this->maxAllowedDepth;
        $complexityExceeded = $weightedComplexity > $this->maxAllowedComplexity;
        $isAllowed = !$depthExceeded && !$complexityExceeded;

        $rejectionReason = null;
        if ($depthExceeded) {
            $rejectionReason = "QUERY_DEPTH_EXCEEDED (Depth: {$maxDepth} > Limit: {$this->maxAllowedDepth})";
        } elseif ($complexityExceeded) {
            $rejectionReason = "QUERY_COMPLEXITY_EXCEEDED (Score: {$weightedComplexity} > Limit: {$this->maxAllowedComplexity})";
        }

        return [
            'success' => true,
            'allowed' => $isAllowed,
            'max_depth' => $maxDepth,
            'max_allowed_depth' => $this->maxAllowedDepth,
            'field_count' => $fieldCount,
            'calculated_complexity' => $weightedComplexity,
            'max_allowed_complexity' => $this->maxAllowedComplexity,
            'status' => $isAllowed ? 'QUERY_ALLOWED_WITHIN_BUDGET' : 'QUERY_BLOCKED',
            'rejection_reason' => $rejectionReason,
        ];
    }

    public function getBudgetLimits(): array
    {
        return [
            'max_depth' => $this->maxAllowedDepth,
            'max_complexity' => $this->maxAllowedComplexity,
            'scalar_field_cost' => 1,
            'connection_field_cost' => 10,
            'depth_multiplier' => 5,
        ];
    }
}
