<?php

namespace Atom\Evaluation;

class PromotionPolicy
{
    /**
     * Evaluates whether a candidate can be safely promoted to production.
     */
    public static function canPromote(array $regressionCheck, bool $humanApproved = false): array
    {
        if ($regressionCheck['has_regression']) {
            return [
                'allowed' => false,
                'reason'  => 'PROMOTION_BLOCKED: Candidate has verified metric regressions: ' . implode(', ', $regressionCheck['reasons']),
            ];
        }

        return [
            'allowed' => true,
            'reason'  => 'PROMOTION_ALLOWED: Candidate passed regression benchmarks and safety criteria',
        ];
    }
}
